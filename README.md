# Docker Mautic setup

Includes PHP FPM 7.1, Mautic 2.15.3, Nginx, PHPMyAdmin and MySQL stacks consolidated into a docker-compose for both local and production use.

The production docker compose uses Traefik as a web proxy.

## Installation

1. Create a `.env` from the `.env.dist` file. Adapt it according to your symfony application

    ```bash
    cp .env.dist .env
    ```


2. Build/run containers with (with and without detached mode)

    ```bash
    $ docker-compose -f docker-compose-local.yml build
    $ docker-compose -f docker-compose-local.yml up -d
    ```

3. Give appropriate permissions for cache and logs directories.

    ```bash
    $ docker-compose -f docker-compose-local.yml run php chown -R www-data:www-data app/cache
    $ docker-compose -f docker-compose-local.yml run php chown -R www-data:www-data app/logs
    ```


4. Warm up cache.

	```bash
	$ docker-compose -f docker-compose-local.yml run php app/console cache:warmup
	```

5. Create local.php and give it appropriate permissions.

    ```bash
    $ docker-compose -f docker-compose-local.yml run php touch app/config/local.php
    $ docker-compose -f docker-compose-local.yml run php chown -R www-data:www-data app/config/local.php
    ```

6. App can be accessed at "http://localhost:8080". Mautic will be installed in this step.



7. After the DB is setup, cp local.php.env to local.php.

    ```bash
    $ docker-compose -f docker-compose-local.yml run php cp app/config/local.php.env app/config/local.php
    ```

## TODOs

- Make local.php more in tune with app specific settings.
- Explore separate caching tier like Redis.
- Send logs to stdout.
- Separate containers for running cronjobs.
