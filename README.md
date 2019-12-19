# Docker Mautic setup

Includes PHP FPM 7.0, Facet's Mautic code base, Nginx, PHPMyAdmin and MySQL stacks consolidated into a docker-compose for both local and production use.

The production docker compose uses Traefik as a web proxy.

## Installation

1. Create a `.env` from the `.env.dist` file. Adapt it according to your symfony application

    ```bash
    cp .env.dist .env
    ```

2. Copy over DB dump into the `db-init` directory.


3. Build/run containers with (with and without detached mode)

    ```bash
    $ docker-compose -f docker-compose-local.yml build
    $ docker-compose -f docker-compose-local.yml up -d
    ```

4. Run composer.

    ```bash
    $ docker-compose -f docker-compose-local.yml run php composer install
    ```



5. Give appropriate permissions for cache and logs directories.

    ```bash
    $ docker-compose -f docker-compose-local.yml run php chown -R www-data:www-data app/cache
    $ docker-compose -f docker-compose-local.yml run php chown -R www-data:www-data app/logs
    ```


4. Warm up cache.

	```bash
	$ docker-compose -f docker-compose-local.yml run php app/console cache:warmup
	```

5. Run DB migrations.

	```bash
	$ docker-compose -f docker-compose-local.yml run php app/console doctrine:migrations:migrate
	```

6. Update admin user password and reset it to `secret`.

    ```sql
	UPDATE users SET password = "$2a$04$LrQYZmEMFi7GghF0EIv4FOdNv8bFcnlXM9Bta0eb8BWLLlRwcKrUm" where id = 1;
    ```

7. App can be accessed at "http://localhost:8080". Mautic will be installed in this step.


## TODOs

- ~~Make local.php more in tune with app specific settings.~~
- ~~Update this mautic to latest stable version.~~
- Explore separate caching tier like Redis. - Will be handled by stateful sets in Kubernetes.
- Send logs to stdout.
- Separate containers for running cronjobs.
