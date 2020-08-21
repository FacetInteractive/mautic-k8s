# Mautic K8s

Includes PHP FPM 7.1, Facet's Mautic code base, Nginx, PHPMyAdmin and MySQL stacks consolidated into a docker-compose for both local and production use.

The production docker compose uses Traefik as a web proxy.

## Documentation Cleanup Tasks

* [ ] Remove docker-compose instructions
* [ ] Add lando instructions
* [ ] Add K8s Instructions under wiki.
* [ ] Organize Wiki based on _standard functions_: `setup`, `build`, `deploy` and _components_: `k8s`, `mautic`, `nginx`  

## Required Access To Build via AWS

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

Save the below code as `eks-admin.yaml`
```
apiVersion: v1
kind: ServiceAccount
metadata:
  name: eks-admin
  namespace: kube-system

---
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
`kubectl apply -f eks-admin.yaml`

### Retrieve the token fo eks-admin service account:

`kubectl -n kube-system describe secret $(kubectl -n kube-system get secret | grep eks-admin | awk '{print $1}')`

Copy the Authentication token from the output


After successfully adding the cluster, Add the `Helm Tiller` application

In the Project Menu, go to Operations --> Kubernetes --> <clustername> --> Applications --> Install Helm Tiller

Cluster role to Gitlab Project to run ci-cd

```bash
kubectl create clusterrolebinding gitlabci-rolebinding \
  --clusterrole=cluster-admin \
  --serviceaccount=mautic-268:mautic-268-service-account
```


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

# Using Helm to install Mautic.

[**Helm**](https://helm.sh/) is a package manager for kubernetes. Helm Charts help you define, install, and upgrade even the most complex Kubernetes application.

helm is the client side component and tiller is the server side component

## Install helm and tiller

Create a Service Account and cluster rolebinding with cluster-admin roe

1. Create a Service Account for Tiller

Create a file with below content and save it as `tiller_service_account.yaml`

```yaml
apiVersion: v1
kind: ServiceAccount
metadata:
  name: tiller
  namespace: kube-system
---
apiVersion: rbac.authorization.k8s.io/v1
kind: ClusterRoleBinding
metadata:
  name: tiller
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
  - kind: ServiceAccount
    name: tiller
    namespace: kube-system
```

kubectl apply -f tiller_service_account.yaml

1. Install helm

```bash
curl -Lo https://git.io/get_helm.sh /tmp/get_helm.sh
chmod 700 /tmp/get_helm.sh
bash /tmp/get_helm.sh
```

1. Initialize helm with tiller

```bash
helm init --service-account tiller
```

1. Confirm installation by checking the pods in the `kube-system` namespace

```bash
kubectl get pods --namespace kube-system
```

A successful helm initialization creates a pod with the name `tiller-deploy-xxxxxxx-xxxx` and it should in running status.

## Storage class for data persisentce

To install mautic in High Available (HA) Mode, the mautic pods need to run on multiple nodes in the K8s cluster. For this, persisent volumes (PV) with `ReadWriteMany(RWX)` Mode are needed. To be able to provision PVs with RWX, a custom storage class needs to be created based on the cloud provider.

When deploying on eks, the following steps can be followed to create a storage class that supports RWX operations.

1. Create a EFS File system in aws.
2. Make sure the Worker Node group has connectivity to the EFS cluster. Refer [Documentation](https://docs.aws.amazon.com/efs/latest/ug/creating-using-create-fs.html) for further help.

```bash
helm install stable/efs-provisioner --set efsProvisioner.efsFileSystemId=fs-12345678 --set efsProvisioner.awsRegion=us-east-2
```

In the above command replace FileSytemId with your EFS File system ID and the region.

## Update configuration details

1. The `values.yaml` file is the single point to load all configuration details for your application. Replace all the relevant details and

2. These values can be overridden at the command line or in your CI-CD pipeline with the `--set` option. For example, if you would like to update the variable `ExternalDbHost` which is under project header, you use the set command like below:

helm upgrade --install $RELEASE_NAME_ /path/to/mautic/helm/chartroot_ --namespace $KUBE_NAMESPACE \ --set project.branch=$CI_COMMIT_BRANCH --set project.mauticImage.tag=$CI_COMMIT_SHORT_SHA \ --set project.nginxImage.tag=$CI_COMMIT_SHORT_SHA \ --set project.ExternalDbHost="

<externaldbhostname>" \</externaldbhostname>

Note: the Variable Name is denoted along with the parent item as defined in Values.yaml For instance to override the `key` value as defined below, use `foo.dict.key`, to override value for `name` use `foo.name`

```yaml
foo:
  name: bar
  dict:
    key: somevalue
```

A new _values.yaml_ file can be cloned from the original values.yaml and the values can be replced with custom values and be passed as an argument to the command pipeline.

helm upgrade --install $RELEASE_NAME_ /path/to/helm/chart _--values_ /path/to/custom_values.yaml_

Read more about _Helm Values_ [here](https://helm.sh/docs/chart_best_practices/values/)

### **Important Values**:

Value                            |                             Description                              |      Default
:------------------------------- | :------------------------------------------------------------------  | :-------------------
project.replicas                 |     Determines the number of Pods created for Mautic Application     |                    2
project.Name                     |         The prefix for the resources created in the cluster          |                hello
ExternalDb.host                  |          End points of an external DB service such as RDS.           |              _Empty_
ingress.domain                   |       The URL would be {{ project.Name }}.{{ ingress.domain }}       | facetinteractive.com
project.persisentce.storageClass | [Storage Class for Persistence](#storage-class-for-data-persisentce) |              aws-efs
cronjobs                         |             Schedule and commands for various cron jobs              |              10 jobs

**Note on Database**: If no value is passed for ExternalDbHost parameter, a Database service is created on the Kubernetes cluster. Follow steps in [DB Import](#import-existing-database)

### Import Existing Database

Once the deployment is completed. Retrieve the Databse password by running the below commands

```bash
kubectl -n <namespace> get secrets {{RELEASE_NAME}}-db-secret -o yaml
```

The key `database-password` is base64 encoded. Decode it with the below command

```bash
echo <database-password-value> | base64 -d
```

Now restore the DB with the existing SQL dump

```
kubectl -n <namespace> exec -i  {{RELEASE_NAME}}-mysql-0 -c mysql -- mysql -u user -p mautic < /path/to/my_local_dump.sql
```

### Cron Jobs.

This deployment deploys 10 cron jobs as listed in the default values.yaml. Comment out or delete any cronjobs that are not required or add new cronjobs as required in the below syntax

```yaml
- name: _name for the cronjob_
  schedule: _Cron Schedule_
  command: '"app/console", _"command"_, "--env=prod"'
```
Example:

```yaml
- name: trigger-campaign
  schedule: "10,25,40,55 _**_"
  command: '"app/console", "mautic:campaigns:trigger", "--env=prod"'
```
### Dependencies/Requirements:

Helm provides an easy way to deploy other helm charts along with the current chart. All that is to be done is include the details of the chart in the `requirements.yaml` file and run `helm dependency update` to include the helm charts in the deployment.

This repository has helm charts for RabbitMQ and Traefik configured.

If new charts are to be added, edit the requirements.yaml under the helm chart and add the chart details as below:

```yaml
- name: <Chartname>
  version: <chart-Version>
  repository: <chart_URL>
```


Example:
```yaml
- name: rabbitmq
  version: '7.2.1'
  repository: 'https://charts.bitnami.com/bitnami'
```

## CI-CD


This project uses .gitlabci for  Continuous Integration and Deployment. There are four stages in the pipeline.

### Environment Variables

The below variables are updated in the Gitlab variables and not as part of `.gitlabci.yml` as these are sensitive.
These can be updated by going to the Repository --> LeftPane --> Settings --> CI/CD --> Variables

| Variable                         |                             Description                           
| :------------------------------- | :---------------------------------------------------------------  |
| AWS_ACCESS_KEY                   | AWS Access ID to push and pull images to ECR.                   |
| AWS_SECRET_ACCESS_KEY            | AWS Secret key to push and pull images to ECR.                  |
| TLS_CERT                         | base64 encoded cert.pem for wildcard SSL for ingress.   |
| TLS_KEY                          | base64 encoded privkey.pem  for wildcard SSL for ingress (non-prod envs). |
| PROD_TLS_CERT                    | base64 encoded cert.pem of SSL cert for Prod ingress . Only if cert for non-prod and prod is different.  |
| PROD_TLS_KEY                     | base64 encoded privkey.pem for SSL cert for Prod ingress. Only if cert for non-prod and prod is different.  |
| RDS_PASSWORD                     | RDS Password used to create secret for RDS |
| RDS_ROOT_PASSWORD                | RDS root Password (optional) |
| APP_SECRET_KEY                   | Secret key for the Mautic application |
| MAILER_SECRET_KEY                | Password for the email host |
| PROD_APP_SECRET_KEY              | Secret key for the Mautic application in Prod env |
| PROD_MAILER_SECRET_KEY           | Password for the email host for Prod env|


Variables used in gitlabci:

Variable                         |                             Description                           
:------------------------------- | :--------------------------------------------------------------- |
RDS_HOST                         | The endpoint of RDS host or external Database service
MAUTIC_REPOSITORY_URL            | ECR URL for Mautic images
NGINX_REPOSITORY_URL             | ECR URL for Nginx images
DEV_BUILD_DISABLED               | comment this out to run the stage  dev-build
PROD_BUILD_DISABLED              | comment this out to run the stage  prod-build
DEV_RELEASE_DISABLED             | comment this out to run dev-deploy stage when commiting from non-master branch
PROD_RELEASE_DISABLED            | comment this out to run dev-deploy stage when commiting from non-master branch

ECR can be replaced with any other Image repository by updating `MAUTIC_REPOSITORY_URL` and `NGINX_REPOSITORY_URL`


- dev-build

  This stage builds mautic and nginx images when the code is commited from non-master branch. The images are tagged with the `$CI_COMMIT_SHORT_SHA` and pushed to ECR repository.

- prod-build

  This stage builds mautic and nginx images when the code is commited from master branch. The images are tagged with the `$CI_COMMIT_SHORT_SHA` and also with the `latest` and pushed to ECR repository.

- dev-deploy

  This stage creates a namespace with the branch name ($CI_COMMIT_BRANCH) when code is commited from non-master branch, if one doesn't exist and deploy the applications with helm charts. If the namespace already exists, the helm upgrade will be run and the application will be upgraded as per the changes. A secret called `regcred` is also created when the code is commited from the branch for the first time. This is used to pull images from ecr.


- prod-deploy

  This stage creates a namespace with the name mautic-code when code is commited from master branch and deploy the applications with helm charts. If the namespace already exists, the helm upgrade will be run and the application will be upgraded as per the changes. A secret called `regcred` is also created when the code is commited from the branch for the first time. This is used to pull images from ecr.

Upon successful execution of the CI-CD, the application is deployed on the respective namespace and the pipeline outputs the commands to find out the URL and the Loadbalancer DNS name (elb endpoint).

Map the CNAME to the ELB endpoint in the DNS records.
