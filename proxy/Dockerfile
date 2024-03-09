FROM nginx:alpine

ARG DOCKERIZE_VERSION=v0.6.1
RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

# add h5bp/server-configs-nginx
RUN mkdir /etc/nginx/h5bp \
    && cd /etc/nginx/h5bp \
    && wget https://github.com/h5bp/server-configs-nginx/archive/refs/tags/3.3.0.tar.gz -O h5bp.tgz \
    && tar xzvf h5bp.tgz \
    && rm -f h5bp.tgz \
    && mv server-configs-nginx-*/h5bp/* . \
    && mv server-configs-nginx-*/nginx.conf /etc/nginx/nginx.conf \
    && sed -i "s|^user .*|user\ nginx nginx;|g" /etc/nginx/nginx.conf \
    && mv server-configs-nginx-*/mime.types /etc/nginx/mime.types \
    && rm -rf server-configs-nginx-*

WORKDIR /app

ENV LISTEN=80 \
    CLIENT_MAX_BODY_SIZE=25M \
    DOMAIN=localhost



COPY default.tmpl /etc/nginx/conf.d/default.tmpl
COPY entrypoint.sh /kool/entrypoint.sh

RUN chmod +x /kool/entrypoint.sh

EXPOSE 80

ENTRYPOINT [ "/kool/entrypoint.sh" ]
CMD ["nginx", "-g", "daemon off;"]
