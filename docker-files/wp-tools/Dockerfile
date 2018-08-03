FROM php:7.2-alpine

RUN apk --no-cache add git subversion openssh mercurial tini bash patch

RUN echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
 && echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "$PHP_INI_DIR/conf.d/date_timezone.ini"

# install the PHP extensions we need
RUN set -ex; \
	\
	apk add --no-cache --virtual .build-deps \
		libjpeg-turbo-dev \
		libpng-dev \
		zlib-dev; \
	docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr; \
	docker-php-ext-install gd mysqli opcache zip; \
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --virtual .wordpress-phpexts-rundeps $runDeps; \
	apk del .build-deps

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=2'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \	
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# install wp-cli dependencies
RUN apk add --update --no-cache \
		less \
		mysql-client \
		unzip 

RUN set -ex; \
	mkdir -p /var/www/html; \
	chmod 777 -R /var/www/html
	# chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html
VOLUME ["/var/www/html", "/var/www/html/wp-content"]

# https://make.wordpress.org/cli/2018/05/31/gpg-signature-change/
# pub   rsa2048 2018-05-31 [SC]
#       63AF 7AA1 5067 C056 16FD  DD88 A3A2 E8F2 26F0 BC06
# uid           [ unknown] WP-CLI Releases <releases@wp-cli.org>
# sub   rsa2048 2018-05-31 [E]
ENV WORDPRESS_CLI_GPG_KEY 63AF7AA15067C05616FDDD88A3A2E8F226F0BC06

ENV WORDPRESS_CLI_VERSION 1.5.1
ENV WORDPRESS_CLI_SHA512 8dd68c98c6fa00e1acc5e036f9393c8b052937045b5232e4aa0eb4f15773908eae48760607bc853a4f951bd5ba69e5050337e5d9dcfa48df87a12cebb1de3432
ENV WORDPRESS_CODING_STANDARDS_VERSION 1.0.0
ENV XDEBUG_VERSION 2.6.0

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME ${HOME}/.composer
ENV COMPOSER_VERSION 1.6.5

RUN set -ex; \
	\
	apk add --no-cache --virtual .fetch-deps \
		gnupg \
	; \
	\
	curl -o /usr/local/bin/wp.gpg -fSLk "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
	\
	export GNUPGHOME="$(mktemp -d)"; \
	( gpg --keyserver hkps://hkps.pool.sks-keyservers.net --recv-keys "$WORDPRESS_CLI_GPG_KEY" \
	|| gpg --keyserver ha.pool.sks-keyservers.net --recv-keys "$WORDPRESS_CLI_GPG_KEY" \
	|| gpg --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys "$WORDPRESS_CLI_GPG_KEY" \
	|| gpg --keyserver pool.sks-keyservers.net --recv-keys "$WORDPRESS_CLI_GPG_KEY" \
	|| gpg --keyserver pgp.mit.edu --recv-keys "$WORDPRESS_CLI_GPG_KEY" \
	|| gpg --keyserver keyserver.pgp.com --recv-keys "$WORDPRESS_CLI_GPG_KEY" ); \
	gpg --batch --decrypt --output /usr/local/bin/wp /usr/local/bin/wp.gpg; \
	rm -rf "$GNUPGHOME" /usr/local/bin/wp.gpg; \
	\
	echo "$WORDPRESS_CLI_SHA512 */usr/local/bin/wp" | sha512sum -c -; \
	chmod +x /usr/local/bin/wp; \
	\
	apk del .fetch-deps; \
	\
	wp --allow-root --version

COPY ./docker-entrypoint.sh /usr/local/bin/

# Installing XDebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install "xdebug-${XDEBUG_VERSION}" \
    && docker-php-ext-enable xdebug

# Installing Composer (requires Git)
RUN curl -s -f -L -k -o /tmp/installer.php https://raw.githubusercontent.com/composer/getcomposer.org/b107d959a5924af895807021fcef4ffec5a76aa9/web/installer \
 && php -r " \
    \$signature = '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061'; \
    \$hash = hash('SHA384', file_get_contents('/tmp/installer.php')); \
    if (!hash_equals(\$signature, \$hash)) { \
        unlink('/tmp/installer.php'); \
        echo 'Integrity check failed, installer is either corrupt or worse.' . PHP_EOL; \
        exit(1); \
    }" \
 && php /tmp/installer.php --no-ansi --install-dir=/usr/bin --filename=composer --version=${COMPOSER_VERSION} \
 && composer --ansi --version --no-interaction \
 && rm -rf /tmp/* /tmp/.htaccess

ENV PATH "${PATH}:${COMPOSER_HOME}/vendor/bin"

# Installing Tools (PHPUnit, PHP Code Sniffer, WordPress-Coding-Standards)
RUN composer global require phpunit/phpunit ^7
RUN composer global require squizlabs/php_codesniffer
RUN composer global require dealerdirect/phpcodesniffer-composer-installer
RUN composer global require wp-coding-standards/wpcs

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Removes the www-data from original dockerfile
# to do changes in wp-config.php. 
# Requires the same user of wordpress image
# ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# USER www-data
# CMD ["wp", "shell", "--allow-root", "--path=/wordpress"]