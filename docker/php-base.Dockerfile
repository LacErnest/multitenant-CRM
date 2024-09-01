ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION}-fpm-alpine AS base_php
ARG BASE_PATH=docker

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
		openssl \
		bash \
		mysql-client \
        libreoffice \
		imagemagick \
	;

ARG APCU_VERSION=5.1.17
RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		postgresql-dev \
		imap-dev \
		zlib-dev \
		libxml2-dev \
		freetype \
        libjpeg-turbo \
        libpng \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
		imagemagick \
	; \
	\
	apk add --no-cache msttcorefonts-installer fontconfig && \
        update-ms-fonts && \
            fc-cache -f; \
    \
	docker-php-ext-configure soap; \
	docker-php-ext-configure zip; \
	docker-php-ext-configure imap; \
	docker-php-ext-install -j$(nproc) \
		intl \
		pdo_mysql \
		zip \
		soap \
		imap \
		mysqli \
		opcache \
		soap \
		exif \
		gd \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
		redis \
		imagick \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
		redis \
		gd \
		imagick \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

COPY ${BASE_PATH}/php/vistafonts-installer /var/cache/vistafonts-installer
RUN chmod +x /var/cache/vistafonts-installer
RUN /var/cache/vistafonts-installer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ${BASE_PATH}/php/php.ini /usr/local/etc/php/php.ini
COPY ${BASE_PATH}/php/zz-fpm.conf /usr/local/etc/php-fpm.d/zz-fpm.conf

#Timezone settings
ENV TZ=${TZ:-UTC}
RUN apk add -U tzdata; \
    cp /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone; \
    apk del tzdata; \
    rm -rf /var/cache/apk/*;

#Browscaps
RUN wget http://browscap.org/stream?q=Lite_PHP_BrowsCapINI -O /usr/local/etc/php/browscap.ini; \
	chmod 777 /usr/local/etc/php/browscap.ini;


# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
    composer global config --no-plugins allow-plugins.symfony/flex true; \
	composer global require "laravel/envoy:~1.0" "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
	composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

RUN git config --global user.email "deployer@emgoz.studio"
RUN git config --global user.name "deployer"
RUN git config --global url.ssh://git@gitlab.cyrextech.net/.insteadOf https://gitlab.cyrextech.net/

WORKDIR /app
