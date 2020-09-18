FROM php:7.2-fpm AS php-7.2-base

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libmcrypt-dev \
    zlib1g-dev \
    mariadb-client \
    libssl-dev \
    libc-client-dev \
    libkrb5-dev \    
    wget \
    libwebp-dev libjpeg62-turbo-dev libpng-dev libxpm-dev \
    libfreetype6-dev \
    && pecl install mcrypt-1.0.1
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/America/Los_Angeles /etc/localtime

# Type docker-php-ext-install to see available extensions
RUN docker-php-ext-configure  imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap
RUN docker-php-ext-install pdo pdo_mysql bcmath zip \
    && docker-php-ext-enable pdo pdo_mysql mcrypt bcmath zip
RUN docker-php-ext-configure gd --with-gd --with-webp-dir --with-jpeg-dir \
    --with-png-dir --with-zlib-dir --with-xpm-dir --with-freetype-dir \
    && docker-php-ext-install gd

# install xdebug
# RUN pecl install xdebug && docker-php-ext-enable xdebug
# RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.idekey=\"PHPSTORM\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM php-7.2-base AS mautic

COPY php7-fpm/php.ini "$PHP_INI_DIR/php.ini"

RUN composer global require hirak/prestissimo

COPY . /var/www/symfony

RUN useradd -u 1001 -r -g 0 -d /app -s /bin/bash -c "Default Application User" default

RUN mkdir /cache && chown -R 1001:0 /cache && chmod -R g+rwX /cache
RUN mkdir /logs && chown -R 1001:0 /logs && chmod -R g+rwX /logs

WORKDIR /var/www/symfony

RUN composer install --no-dev --prefer-dist --no-interaction --no-ansi --optimize-autoloader

RUN chown -R 1001:0 /var/www/symfony && chmod -R g+rwX /var/www/symfony

USER 1001


FROM nginx:1.17 AS nginx

RUN useradd -u 1001 -r -g 0 -d /app -s /sbin/nologin -c "Default Application User" default \
    && mkdir -p /app \
    && chown -R 1001:0 /app && chmod -R g+rwX /app

COPY nginx.conf /etc/nginx
COPY symfony.conf /etc/nginx/conf.d/default.conf

COPY --from=mautic /var/www/symfony /var/www/symfony


RUN chown -R 1001:0 /var/log && chmod -R g+rwX /var/log
RUN chown -R 1001:0 /var/cache/nginx && chmod -R g+rwX /var/cache/nginx
RUN chown -R 1001:0 /var/run && chmod -R g+rwX /var/run
RUN chown -R 1001:0 /etc/nginx && chmod -R g+rwX /etc/nginx

EXPOSE 8080
