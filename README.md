![CI/CD](https://github.com/kool-dev/docker-nginx/workflows/CI/CD/badge.svg)

## Description

Minimal [NGINX](https://www.nginx.com/) Docker image. It's use is intended for [kool.dev](https://github.com/kool-dev/kool), but can fit in any other NGINX use-case.

## Available Tags

- [php](https://github.com/kool-dev/docker-nginx/blob/master/php/Dockerfile)
- [static](https://github.com/kool-dev/docker-nginx/blob/master/static/Dockerfile)

## Environment Variables

Variable | Default Value | Description
--- | --- | ---
**LISTEN** | `80` | Changes the PORT address
**ROOT** | `/app/public` | Changes NGINX root directive
**INDEX** | `index.php` | Changes the index directive
**CLIENT_MAX_BODY_SIZE** | `25M` | Changes maximum allowed size of the client request body
**PHP_FPM** | `app:9000` | Changes the address of a FastCGI server
**FASTCGI_READ_TIMEOUT** | `60s` | Changes a timeout for reading a response from the FastCGI server

## Usage

With `docker run`:

```sh
docker run -it --rm kooldev/nginx:php nginx -v
```

With environment variables:

```sh
docker run -it --rm -e LISTEN=8080 kooldev/nginx:php nginx -v
```

With `docker-compose.yml`:

```yaml
app:
  image: kooldev/nginx:php
  environment:
    LISTEN: "8080"
```

## Contributing

### Dependencies

- [fwd](https://github.com/fireworkweb/fwd#fireworkwebfwd)

You should change `fwd-template.json` and `template` folder.

After your changes, just run `fwd template` to compile the template and generate all version folder/files.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
