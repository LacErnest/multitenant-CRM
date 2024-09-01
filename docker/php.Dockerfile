ARG BASE_IMAGE="registry-gitlab.cyrextech.net/dev-awesome/oz-finance/php"
#=======================================================================================================================
# api stage
FROM ${BASE_IMAGE}:latest as api_php
ARG BASE_PATH=docker
ARG GITLAB_COMPOSER_TOKEN
RUN apk update; \
    apk add --no-cache imagemagick \
    ghostscript;

COPY ./api/ ./

RUN set -eux; \
    composer config --global --auth gitlab-token.gitlab.cyrextech.net "${GITLAB_COMPOSER_TOKEN}"; \
    composer config --global gitlab-domains "gitlab.cyrextech.net"; \
	composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest; \
	composer clear-cache; \
	chmod +x artisan;

RUN cp vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64 /usr/local/bin/wkhtmltoimage
RUN cp vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64 /usr/local/bin/wkhtmltopdf
RUN chmod +x /usr/local/bin/wkhtmltoimage 
RUN chmod +x /usr/local/bin/wkhtmltopdf

COPY ${BASE_PATH}/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint


RUN mkdir -p storage/logs; \
    chmod -R 777 storage; \
    chown -R www-data:www-data storage

VOLUME [ "/app/storage/framework", "/app/public" ]

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# "cli" stage
FROM api_php as cli_php
ARG BASE_PATH=docker

RUN apk update; \
 apk add --no-cache supervisor \
 imagemagick \
 ghostscript; \
 touch /var/log/cron.log;

COPY ${BASE_PATH}/supervisor/supervisord.conf /etc/supervisord.conf
COPY ${BASE_PATH}/cron/crontab /etc/crontabs/root

ENTRYPOINT []
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
