ARG NGINX_VERSION=1.15

FROM node:14.15.3-alpine AS build_front

WORKDIR /app

COPY ./angular/package*.json /app/

RUN npm install

COPY ./angular /app/

ARG configuration=production

RUN npm run build -- --output-path=./dist/out --configuration $configuration

FROM nginx:${NGINX_VERSION}-alpine AS api_platform_nginx
ARG BASE_PATH=docker

COPY ${BASE_PATH}/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

COPY --from=build_front /app/dist/out/ /app/api/public/

VOLUME [ "/app/api/public" ]

WORKDIR /app
