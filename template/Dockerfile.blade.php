FROM nginx:alpine

ARG DOCKERIZE_VERSION=v0.6.1
RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

WORKDIR /app

ENV LISTEN=80 \
    CLIENT_MAX_BODY_SIZE=25M \
    DOMAIN={{ $version === 'proxy' ? 'localhost' : '_' }}

@if ($version !== 'proxy')
ENV INDEX={{ $index }} \
    ROOT=/app/public
@endif

@if ($version === 'php')
ENV PHP_FPM=app:9000 \
    FASTCGI_READ_TIMEOUT=60s \
    FASTCGI_BUFFERS='8 8k' \
    FASTCGI_BUFFER_SIZE='16k'
@endif

COPY default.tmpl /etc/nginx/conf.d/default.tmpl

EXPOSE 80

CMD ["dockerize", "-template", "/etc/nginx/conf.d/default.tmpl:/etc/nginx/conf.d/default.conf", "nginx", "-g", "daemon off;"]
