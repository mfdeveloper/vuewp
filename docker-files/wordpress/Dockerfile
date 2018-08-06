# See: https://stackoverflow.com/questions/46825502/how-do-i-install-xdebug-on-dockers-official-php-fpm-alpine-image#46831699

FROM wordpress:4.9.8

ENV XDEBUG_VERSION 2.6.0

RUN pecl install "xdebug-${XDEBUG_VERSION}" \
    && docker-php-ext-enable xdebug 