#!/bin/sh

set -e

dockerize -template /etc/nginx/conf.d/default.tmpl:/etc/nginx/conf.d/default.conf

exec sh /docker-entrypoint.sh "$@"
