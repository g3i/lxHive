FROM nginx:alpine

RUN apk add --no-cache bash

# Copy the nginx config file
COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf
