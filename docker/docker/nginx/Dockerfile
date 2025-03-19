FROM nginxinc/nginx-unprivileged

####
# https://hub.docker.com/r/nginxinc/nginx-unprivileged
# https://github.com/nginxinc/docker-nginx-unprivileged/pkgs/container/nginx-unprivileged
####

RUN apk add --no-cache bash

# Copy the nginx config file
COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf
