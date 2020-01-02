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

RUN chown -R www-data:www-data /var/www/symfony/app/logs
RUN chown -R www-data:www-data /var/www/symfony/app/cache
WORKDIR /var/www/symfony
RUN composer install
RUN mkdir /cache && chown -R www-data:www-data /cache
RUN mkdir /logs && chown -R www-data:www-data /logs
USER www-data
