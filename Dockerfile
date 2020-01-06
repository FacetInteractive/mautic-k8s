FROM php:7.1-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libmcrypt-dev \
    zlib1g-dev \
    mariadb-client-10.3 \
    wget
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime
RUN "date"

# Type docker-php-ext-install to see available extensions  
RUN docker-php-ext-install pdo pdo_mysql mcrypt bcmath zip \
    && docker-php-ext-enable pdo pdo_mysql mcrypt bcmath zip

# install xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.idekey=\"PHPSTORM\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


COPY mautic /var/www/symfony

RUN useradd -u 1001 -r -g 0 -d /app -s /bin/bash -c "Default Application User" default \
    && chown -R 1001:0 /var/www/symfony && chmod -R g+rwX /var/www/symfony


RUN mkdir /cache && chown -R 1001:0 /cache && chmod -R g+rwX /cache
RUN mkdir /logs && chown -R 1001:0 /logs && chmod -R g+rwX /logs

WORKDIR /var/www/symfony

RUN composer install

USER 1001
