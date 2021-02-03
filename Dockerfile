FROM bitnami/php-fpm:7.3-prod AS mautic

RUN apt-get update && apt-get install redis-tools unzip git build-essential libtool autoconf unzip wget -y

RUN pecl install redis

COPY infra/php7-fpm/php.ini /opt/bitnami/php/etc/php.ini

RUN composer global require hirak/prestissimo

COPY . /var/www/symfony

RUN useradd -u 1001 -r -g 0 -d /app -s /bin/bash -c "Default Application User" default

RUN mkdir /cache && chown -R 1001:0 /cache && chmod -R g+rwX /cache
RUN mkdir /logs && chown -R 1001:0 /logs && chmod -R g+rwX /logs
RUN mkdir /mnt/media && chown -R 1001:0 /mnt/media && chmod -R g+rwX /mnt/media
RUN mkdir /mnt/spool && chown -R 1001:0 /mnt/spool && chmod -R g+rwX /mnt/spool

WORKDIR /var/www/symfony

RUN composer install --no-dev --prefer-dist --no-interaction --no-ansi --optimize-autoloader --verbose

RUN ln -s /mnt/media /var/www/symfony/mautic/media

RUN chown -R 1001:0 /var/www/symfony && chmod -R g+rwX /var/www/symfony

USER 1001


FROM nginx:1.17 AS nginx

RUN useradd -u 1001 -r -g 0 -d /app -s /sbin/nologin -c "Default Application User" default \
    && mkdir -p /app \
    && chown -R 1001:0 /app && chmod -R g+rwX /app

COPY infra/nginx/nginx.conf /etc/nginx
COPY infra/nginx/symfony.conf /etc/nginx/conf.d/default.conf

COPY --from=mautic /var/www/symfony /var/www/symfony


RUN chown -R 1001:0 /var/log && chmod -R g+rwX /var/log
RUN chown -R 1001:0 /var/cache/nginx && chmod -R g+rwX /var/cache/nginx
RUN chown -R 1001:0 /var/run && chmod -R g+rwX /var/run
RUN chown -R 1001:0 /etc/nginx && chmod -R g+rwX /etc/nginx

EXPOSE 8080
