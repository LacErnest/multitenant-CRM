#!/bin/sh

set -e

composer install --optimize-autoloader --prefer-dist --no-progress --no-suggest --no-interaction;

php artisan o:c;
composer dump-autoload;

if [ ! -f "/app/.env" ]; then
  php artisan key:generate;
  php artisan jwt:secret;
fi

mkdir -p storage/logs

php artisan storage:link;

if [[ -n "${APP_ENV}" && "${APP_ENV}" != 'prod' && "${APP_ENV}" != 'local' && "${APP_ENV}" != 'testing' && "${APP_ENV}" != 'dev' && "${APP_ENV}" != 'staging' ]]; then
  php artisan tenant:migrate_refresh;
fi

if [[  "${APP_ENV}" == 'dev' || "${APP_ENV}" == 'staging' ]]; then
  php artisan tenant:migrate_update;
fi

chmod -R 777 storage
chown -R www-data:www-data storage

docker-php-entrypoint "$@"