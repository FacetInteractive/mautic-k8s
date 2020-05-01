# Docker Mautic setup

Includes PHP FPM 7.1, Facet's Mautic code base, Nginx, PHPMyAdmin and MySQL stacks consolidated into a docker-compose for both local and production use.

The production docker compose uses Traefik as a web proxy.

## Required Access To Build

* Have AWS CLI installed and configured
    * Setup a profile for your AWS credentials
    * Region: `us-west-1`

* Login to AWS ECR

    ```bash
    # Request ECR Login
    aws ecr get-login --no-include-email
  
    # With a profile
    aws --profile {facet.mktg} ecr get-login --no-include-email
    ```

## Installation

1. Create a `.env` from the `.env.dist` file. Adapt it according to your symfony application

    ```bash
    cp .env.dist .env
    ```

2. Copy over DB dump into the `db-init` directory.


3. Replace the below line in `symfony.conf`,

        fastcgi_pass 127.0.0.1:9000;
with,

        fastcgi_pass php:9000;

For local, php and nginx run as 2 separate services. For K8s though, they are 2 processes part of the same container.

4. Build/run containers with (with and without detached mode)

    ```bash
    $ docker-compose -f docker-compose-local.yml build
    $ docker-compose -f docker-compose-local.yml up -d
    ```

5. Warm up cache.

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
- ~~Send logs to stdout.~~
- ~~Separate containers for running cronjobs.~~


# Kubernetes setup

## Prerequisites

1. Working Kubernetes cluster with ingress controller. This code is tested with EKS, but should work with minor tweaks in other providers.

2. AWS ECR. Used to push the Mautic and Nginx docker images we build. This can be substituted with Gitlab registry or any other container registry provider we give the appropriate image pull secrets in the Deployment spec.

3. You are logged into your AWS account using the aws cli tool. 

4. docker installed on your local.

5. kubectl binary for interacting with Kubernetes cluster.

## Building the RabbitMQ image

```
cd k8s/rabbitmq
$(aws ecr get-login --no-include-email --region us-west-1)
docker build -t rabbitmq:3.8 .
docker tag rabbitmq:3.8 993385208142.dkr.ecr.us-west-1.amazonaws.com/rabbitmq:3.8
docker push 993385208142.dkr.ecr.us-west-1.amazonaws.com/rabbitmq:3.8
```

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
docker build -f Dockerfile-nginx -t facet-mautic-nginx:1.6 .
docker tag facet-mautic-nginx:1.6 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic-nginx:1.6
docker push 993385208142.dkr.ecr.us-west-1.amazonaws.com/facet-mautic-nginx:1.6
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
kubectl apply -f k8s/rabbitmq/rabbitmq.yml -n mautic
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

## How to clear cache and then warm it

Login to mautic shell.

```
kubectl exec -it  mautic-0 -n mautic  -c mautic -- /bin/bash
```

Remove cache dir contents.

```
cd /cache
rm -rf *
```

Warm up cache.

```
cd /var/www/symfony
app/console cache:warmup
```

If you get a PHP OOM error, re-run the last command.

```
app/console cache:warmup
```




# Kubernetes Integration with Gitlab

## Add existing Cluster to Gitlab


### Import the kube-config:

`aws eks --region us-east-1 update-kubeconfig --name <cluster_name>`
	
Note: check if your AWS_PROFILE is set if you're using multiple aws profiles.

Confirm if your able to read cluster resources:

`kubectl get pods -A`


### Get Kubernetes URL: 

`kubectl cluster-info | grep 'Kubernetes master' | awk '/http/ {print $NF}'`


### Get Kubernetes Certificate


Get list of all secrets:	
 	
`kubectl get secrets`
 	
There is a secret named " `default-token-<random-string>` . get that secret:	
 			
`kubectl get secret <secret-name> -o jsonpath="{['data']['ca\.crt']}" | base64 --decode`


### Create a cluster-admin Service account

Save the below code as a `eks-admin-service-account.yaml`

```
apiVersion: v1
kind: ServiceAccount
metadata:
  name: eks-admin
  namespace: kube-system
``` 

Then run
`kubectl apply -f eks-admin-service-account.yaml`

### Create a role binding

Save the below code as `eks-admin-rolebinding.yaml`

```
apiVersion: rbac.authorization.k8s.io/v1beta1
kind: ClusterRoleBinding
metadata:
  name: eks-admin
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
- kind: ServiceAccount
  name: eks-admin
  namespace: kube-system
```

Then run
`kubectl apply -f eks-admin-rolebinding.yaml`

### Retrieve the token fo eks-admin service account:

`kubectl -n kube-system describe secret $(kubectl -n kube-system get secret | grep eks-admin | awk '{print $1}')`

Copy the Authentication token from the output



## Cron jobs in Kubernetes

Cron is managed by Kubernetes cron constructs. This creates a new pod with the same mautic PHP docker image as the running stateful set.

The pod lifecycle is the duration of the cron job. The pod gets terminated upon ternmiation of the process which gets triggered from the cron job, successful or otherwise.

The cron logs are stored and can be queried from the cli using the command:

```
$ kubectl logs mautic-cron-trigger-campaign-1580262660-b7fjl -n mautic
Triggering events for campaign 42
Triggering events for newly added contacts
0 total events(s) to be processed in batches of 100 contacts

0 total events were executed
0 total events were scheduled

Triggering scheduled events
0 total events(s) to be processed in batches of 100 contacts

0 total events were executed
0 total events were scheduled

Triggering events for inactive contacts

0 total events were executed
0 total events were scheduled
```


To get the pod name:

```
$ kubectl get pod -n mautic
NAME                                            READY     STATUS              RESTARTS   AGE
mautic-0                                        2/2       Running             0          34h
mautic-1                                        2/2       Running             0          34h
mautic-2                                        0/2       ContainerCreating   0          22h
mautic-3                                        2/2       Running             0          22h
mautic-cron-trigger-campaign-1580263740-sf2d2   0/1       Completed           0          9m3s
mautic-cron-trigger-campaign-1580263920-6xvrb   0/1       Completed           0          6m3s
mautic-cron-trigger-campaign-1580264100-c59l9   0/1       Completed           0          3m2s
mautic-cron-trigger-campaign-1580264280-6gm6g   1/1       Running             0          2s
``` 

To alter cron schedule, edit the cron job and change the `spec/schedule` entry.

To alter the cron process, edit the cron job and change the `jobTemplate/spec/template/containers/command` entry.

**NOTE** Each crontab entry will be a new cron job construct in the cluster and they are all mutually exclusive.

## TODOs/Improvements
- ~~Expose logs through stdout/stderr~~
- ~~Add RabbitMQ service~~
- Convert the above YML into a Helm chart
- ~~Add a separate cron resource for runnning periodic tasks~~
- Test with a from scratch Mautic setup
- Periodic DB snapshot backups/restores
- ~~Convert ingress into TLS using ACME/Let's Encrypt.~~


