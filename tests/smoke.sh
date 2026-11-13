#!/usr/bin/env bash
set -euo pipefail

variant=${1:?Usage: smoke.sh php|proxy|static IMAGE}
image=${2:?Usage: smoke.sh php|proxy|static IMAGE}
case "$variant" in php|proxy|static) ;; *) exit 2 ;; esac
fixtures=$(cd "$(dirname "$0")/fixtures" && pwd)
name="kool-nginx-test-${variant}-$$"
backend="${name}-backend"
network="${name}-network"
tmp=$(mktemp -d)

cleanup() {
    status=$?
    if (( status != 0 )); then
        docker logs "$name" 2>/dev/null || true
        docker logs "$backend" 2>/dev/null || true
    fi
    docker rm -f "$name" "$backend" >/dev/null 2>&1 || true
    docker network rm "$network" >/dev/null 2>&1 || true
    rm -rf "$tmp"
    exit "$status"
}
trap cleanup EXIT

docker network create "$network" >/dev/null
if [[ $variant == php ]]; then
    docker run -d --pull always --name "$backend" --network "$network" --network-alias app \
        -v "$fixtures:/srv/site:ro" php:8.4-fpm-alpine >/dev/null
elif [[ $variant == proxy ]]; then
    docker run -d --pull always --name "$backend" --network "$network" --network-alias app \
        -v "$fixtures:/usr/share/nginx/html:ro" nginx:alpine >/dev/null
fi

# Validate the default rendered configuration (PHP needs a resolvable upstream).
docker run --rm --network "$network" "$image" nginx -v
docker run --rm --network "$network" "$image" nginx -t

env_args=(-e LISTEN=8080 -e DOMAIN=example.test -e CLIENT_MAX_BODY_SIZE=1k)
if [[ $variant != proxy ]]; then
    env_args+=(-e ROOT=/srv/site)
fi
if [[ $variant == php ]]; then
    env_args+=(-e INDEX=front.php -e PHP_FPM=app:9000 -e FASTCGI_READ_TIMEOUT=10s
        -e 'FASTCGI_BUFFERS=4 16k' -e FASTCGI_BUFFER_SIZE=16k)
fi
docker run --rm --network "$network" "${env_args[@]}" "$image" nginx -t

# Invalid configuration must fail, rather than accidentally testing nginx -v again.
if docker run --rm --network "$network" -e LISTEN=not-a-port "$image" nginx -t; then
    echo "Invalid LISTEN unexpectedly accepted" >&2
    exit 1
fi

docker run -d --name "$name" --network "$network" "${env_args[@]}" \
    -p 127.0.0.1::8080 -v "$fixtures:/srv/site:ro" "$image" >/dev/null
port=$(docker port "$name" 8080/tcp | cut -d: -f2)
url="http://127.0.0.1:$port"
host=example.test
[[ $variant != proxy ]] || host=app.example.test
request() {
    curl --silent --show-error --max-time 5 -H "Host: $host" "$@"
}
ready=false
for attempt in {1..30}; do
    if request --fail "$url/asset.txt" >"$tmp/body"; then
        ready=true
        break
    fi
    echo "Waiting for $variant HTTP readiness ($attempt/30)"
    sleep 1
done
[[ $ready == true ]]
grep -qx 'kool asset fixture' "$tmp/body"

case "$variant" in
    php)
        request --fail "$url/missing-route?value=query-ok" >"$tmp/body"
        grep -qx 'kool php fixture|query-ok|/srv/site/front.php' "$tmp/body"
        ;;
    static)
        request --fail -D "$tmp/headers" "$url/missing-route" >"$tmp/body"
        grep -qx 'kool static fixture' "$tmp/body"
        grep -qi '^X-Served-By: kool.dev' "$tmp/headers"
        ;;
    proxy)
        request --fail "$url/" >"$tmp/body"
        grep -qx 'kool static fixture' "$tmp/body"
        [[ $(request -o /dev/null -w '%{http_code}' "$url/missing-route") == 404 ]]
        ;;
esac
[[ $(request -o /dev/null -w '%{http_code}' "$url/.htpasswd") == 403 ]]
head -c 2048 /dev/zero >"$tmp/large-body"
[[ $(request -o /dev/null -w '%{http_code}' --data-binary @"$tmp/large-body" "$url/") == 413 ]]
echo "$variant: configuration and HTTP tests passed"
