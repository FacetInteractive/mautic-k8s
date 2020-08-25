FROM lakshminp/php-base:7.2

COPY mautic /var/www/symfony

RUN useradd -u 1001 -r -g 0 -d /app -s /bin/bash -c "Default Application User" default \
    && chown -R 1001:0 /var/www/symfony && chmod -R g+rwX /var/www/symfony

RUN mkdir /cache && chown -R 1001:0 /cache && chmod -R g+rwX /cache
RUN mkdir /logs && chown -R 1001:0 /logs && chmod -R g+rwX /logs

WORKDIR /var/www/symfony

RUN mkdir -p /var/www/symfony/translations

RUN composer install --no-dev --prefer-dist --no-interaction --no-ansi --optimize-autoloader

USER 1001
