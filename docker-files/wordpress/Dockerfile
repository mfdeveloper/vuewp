# See: https://stackoverflow.com/questions/46825502/how-do-i-install-xdebug-on-dockers-official-php-fpm-alpine-image#46831699

FROM wordpress:latest

RUN pecl install xdebug-2.6.0 \
    && docker-php-ext-enable xdebug