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
- ~~Explore separate caching tier like Redis. - Will be handled by stateful sets in Kubernetes.~~
- ~~Env specific settings.~~
- Send logs to stdout.
- Separate containers for running cronjobs.


# Kubernetes setup

## Prerequisites

1. Working Kubernetes cluster with ingress controller. This code is tested with EKS, but should work with minor tweaks in other providers.

2. AWS ECR. Used to push the Mautic and Nginx docker images we build. This can be substituted with Gitlab registry or any other container registry provider we give the appropriate image pull secrets in the Deployment spec.

3. You are logged into your AWS account using the aws cli tool. 

4. docker installed on your local.

5. kubectl binary for interacting with Kubernetes cluster.

## Step 1: Build the containers

Login to the AWS container registry.

```bash
$(aws ecr get-login --no-include-email --region us-west-1)
```

Build, tag and deploy the mautic image.

```bash
docker build -t facet-mautic:2.15.3 .
docker tag facet-mautic:2.15.3 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic:2.15.3
docker push 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic:2.15.3
```

Build, tag and deploy the nginx image.

```bash
docker build -f Dockerfile-nginx -t facet-mautic-nginx:1.1 .
docker tag facet-mautic-nginx:1.1 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic-nginx:1.1
docker push 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic-nginx:1.1
```

**Note** We might eliminate the need for a separate nginx image in future by co-mounting the same volume in both containers.

## Step 2: Deploy the new images to Kubernetes

First, login to the kubernetes cluster using the kubeconfig file provided to you.

```bash
export KUBECONFIG=/path/to/kubeconfig
```

Create the `mautic` namespace.

```bash
kubectl create ns mautic
```

Create the artifacts required to run Mautic in the cluster.

```bash
kubectl apply -f k8s/mautic.yml -n mautic
```

This will create 
1. a highly available stateful set for mautic, running 2 containers, one each for mautic(php-fpm) and nginx.
2. expose the above statefulset as a service
3. a persistent volume each for cache and logs directory.(logs will be emitted to stdout/stderr in the near future).
4. A deployment, persistent volume and a service for MySQL.
5. A secret resource for MySQL credentials
6. An ingress for the mautic instance.

To dive into the mautic shell, run

```bash
kubectl exec -it  mautic-0 -n mautic  -c mautic -- /bin/bash
```

To increase the number of HA replicas, change the count in the yaml file,

```yml
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: mautic 
  labels:
    app: facet-mautic
spec:
  serviceName: mautic
  replicas: 4
```

and apply the changes again.

```bash
kubectl apply -f k8s/mautic.yml -n mautic
```

## TODOs/Improvements
- Expose logs through stdout/stderr
- Add RabbitMQ service
- Convert the above YML into a Helm chart
- Add a separate cron resource for runnning periodic tasks
- Test with a from scratch Mautic setup
- Periodic DB snapshot backups/restores
- Convert ingress into TLS using ACME/Let's Encrypt.
