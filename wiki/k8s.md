# Kubernetes (K8s) Setup

For instructions on how to _manually_ deploy `mautic-k8s` to AWS EKS, [see the documentation here](./wiki/k8s-eks-how-to-manually-deploy.md).

# Kubernetes Integration with GitLab

## How to Add Existing Cluster to GitLab

### Import the kube-config:

```bash
aws eks --region us-east-1 update-kubeconfig --name <cluster_name>`
```

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

Cluster role to GitLab Project to run ci-cd

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

Install helm

```bash
curl -Lo https://git.io/get_helm.sh /tmp/get_helm.sh
chmod 700 /tmp/get_helm.sh
bash /tmp/get_helm.sh
```

Initialize helm with tiller

```bash
helm init --service-account tiller
```

Confirm installation by checking the pods in the `kube-system` namespace

```bash
kubectl get pods --namespace kube-system
```

A successful helm initialization creates a pod with the name `tiller-deploy-xxxxxxx-xxxx` and it should be in running status.

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


This project uses `.gitlab-ci.yml` for  Continuous Integration and Deployment. There are four stages in the pipeline.

### Environment Variables

The below variables are updated in the GitLab variables and not as part of `.gitlab-ci.yml` as these are sensitive.
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


Variables used in GitLab CI:

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


## Deleting an Environment

To delete any particular environment:

1. Find the helm `release` and `namespace` corresponding to the branch. The release name is typically `mautci-$CI_COMMIT_BRANCH`

```bash
helm ls
```
The output would be simlar to this

```bash
NAME                    REVISION        UPDATED                         STATUS          CHART                           APP VERSION     NAMESPACE       
efk                     1               Thu Jan 23 15:05:57 2020        DEPLOYED        efk-0.4.1                       6.4.2           logging         
logging                 1               Thu Jan 23 15:05:42 2020        DEPLOYED        elasticsearch-operator-0.1.7    0.3.0           default         
mautic-dev              11              Fri Aug 21 15:41:47 2020        DEPLOYED        mautic-0.1.0                    1.0             dev             
mautic-master           21              Sat Aug 22 05:56:48 2020        DEPLOYED        mautic-0.1.0                    1.0             mautic-prod     
```

The first column NAME corresponds to the release name of the helm chart and the last column is the NAMESPACE

To delete the helm release, run

```bash
helm delete --purge <release_name>
```

Once the helm release is deleted, ensure the `namespace` is deleted to clean up any pre-install resources

```bash
kubectl delete namespace <namespace>
```