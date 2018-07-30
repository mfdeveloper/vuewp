FROM php:7.2-alpine

# install the PHP extensions we need
RUN set -ex; \
	\
	apk add --no-cache --virtual .build-deps \
		libjpeg-turbo-dev \
		libpng-dev \
	; \
	\
	docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr; \
	docker-php-ext-install gd mysqli opcache; \
	\
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
RUN apk add --no-cache \
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
ENV KEYSERVER hkp://p80.pool.sks-keyservers.net:80
# ENV KEYSERVER hkps://hkps.pool.sks-keyservers.net

RUN set -ex; \
	\
	apk add --no-cache --virtual .fetch-deps \
		gnupg \
	; \
	\
	curl -o /usr/local/bin/wp.gpg -fSLk "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
	\
	export GNUPGHOME="$(mktemp -d)"; \
	gpg --keyserver "${KEYSERVER}" --recv-keys "$WORDPRESS_CLI_GPG_KEY"; \
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

# Install PHPCodeSniffer + WordPress-Coding-Standards
RUN curl -o /usr/local/bin/phpcs.phar -Lk https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar; \
	chmod 777 /usr/local/bin/phpcs.phar
RUN curl -o /usr/local/bin/phpcbf.phar -Lk https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar; \
	chmod 777 /usr/local/bin/phpcbf.phar
RUN curl -o "/home/www-data/WordPress-Coding-Standards-${WORDPRESS_CODING_STANDARDS_VERSION}.zip" -fSLk "https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/${WORDPRESS_CODING_STANDARDS_VERSION}.zip"
RUN cd /home/www-data && unzip "/home/www-data/WordPress-Coding-Standards-${WORDPRESS_CODING_STANDARDS_VERSION}.zip"; \
	rm -f "/home/www-data/WordPress-Coding-Standards-${WORDPRESS_CODING_STANDARDS_VERSION}.zip"
RUN phpcs.phar --config-set installed_paths "/home/www-data/WordPress-Coding-Standards-${WORDPRESS_CODING_STANDARDS_VERSION}"

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Removes the www-data from original dockerfile
# to do changes in wp-config.php. 
# Requires the same user of wordpress image
# ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# USER www-data
# CMD ["wp", "shell", "--allow-root", "--path=/wordpress"]