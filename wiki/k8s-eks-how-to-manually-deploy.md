# How to Manually Deploy `mautic-k8s` to EKS

## @TODO
- [ ] Refresh these instructions to be more agnostic of Facet Mautic.

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