# Mautic K8s

**Deploy Mautic in a scalable Kubernetes cluster.**

This project is an _opinionated_ way to deploy Mautic using:

- **Helm 3 charts** to manage deploying Mautic 2.x
- **nginx-proxy** as our ingress
- **PHP-FPM** with PHP 7.2
- **Nginx** on each application server to work with php-fpm
- **PVC on EFS** for Mautic `media`, `spool`, `cache`, and `logs`.
- **EFK** in cluster
- **Redis** for shared php sessions (but we hope to use this for another Cache plugin soon)
- **RabbitMQ**
- **GitLab CI/CD** with multidev branches
- **RDS** for managed MariaDB

### Notes on Opinionated Mautic Setup

- We make `local.php` read-only with an empty array and require developers to configure everything in `parameters_local.php` where we insert environment based switches.
- We use specialized bash scripts to programmatically link `vendor` and `bin` directories, along with `/mautic_overrides` which are specified in a relative folder path to the `/mautic` project. 

## Requirements

The following are current requirements to work with this project, but considering the interoperability of Helm Charts, we plan to support other Kubernetes platforms and configurations in the future. 

- [AWS Account](https://aws.amazon.com/) - To deploy to AWS EKS.
- [GitLab](https://gitlab.com/) - To leverage CI/CD to deploy. 
- [Helm CLI](https://helm.sh/) - To manage your Kubernetes instance.
- [Lando](https://lando.dev/) - To develop locally.

## Supported K8s Backplanes

- [x] AWS EKS - Amazon Elastic Container Service for Kubernetes
- [ ] GKE - Google Kubernetes Engine
- [ ] AKS - Azure Kubernetes Service
- [ ] Digital Ocean Kubernetes
- [ ] Rancher Kubernetes

_[Open an issue](https://github.com/FacetInteractive/mautic-k8s/issues/new) if you plan to work on one of the unchecked Kubernetes providers, we'd love to know!_

# Current Major Initiatives

- [x] Upgrade to Mautic 2.16.4
- [x] Upgrade to PHP 7.3
- [x] Confirm Redis Connection in PHP 7.3 for Shared Sessions
- [ ] Copy/backfill dev databases as new dev* environments are provisioned
- [ ] Branch to support Mautic 3.x / PHP 7.4

# Get Started Mautic-K8s

1. Fork this repo
2. Copy the following files for your project's configurations:

```bash
cp infra/k8s/values.default.yaml infra/k8s/values.yaml

cp mautic_override/app/config/local.default.php mautic_override/app/config/local.php

cp mautic_override/app/config/parameters_local.default.php mautic_override/app/config/parameters_local.php
```

# Local Mautic Dev With Lando

For instructions on how to set up `mautic-k8s` with Lando, [see the documentation here](./wiki/local-dev-getting-started.md)

# K8s Setup

For instructions on how to deploy `mautic-k8s` for the first time, [see the documentation here](./wiki/k8s.md)

## Environment Variables

The following environment variables must be set in GitLab CI or an equivalent CI pipeline are below.

**NOTE**: To get a better understanding of how these variables are used you will have to follow the daisy chain of these environment variables from `.gitlab-ci.yml` to `infra/k8s/values.default.yaml` to `infra/k8s/templates/mautic_statefulset.yaml`


|Key                            |Description                    |Example                            |Environment|
|-------------------------------|-------------------------------|-----------------------------------|-----------|
|DEV_APP_SECRET_KEY             |Mautic Application Secret Key  |                                   |DEV|
|DEV_GA_TRACKING_ID             |Google Analytics Tracking Code |UA-123456789-1                     |DEV|
|DEV_INGRESS_DOMAIN             |Ingress Domain for K8s         |k8s.mydomain.com                   |DEV|
|DEV_MAILER_HOST                |Amazon SES Host (full URL)     |email-smtp.us-east-1.amazonaws.com |DEV|
|DEV_MAILER_REGION              |Amazon Mailer Region (full URL)|email-smtp.us-east-1.amazonaws.com |DEV|
|DEV_MAILER_SECRET_KEY          |Amazon SES IAM Secret Key      |awsIamSecretKey                    |DEV|
|DEV_MAILER_USER                |Amazon SES IAM User            |awsIamUser                         |DEV|
|DEV_SAML_IDP_IDENTITY_ID       |Typically the hostname of your marketing domain|https://www.mydomain.com|DEV|
|ECR_REGISTRY                   |Subdomain for your ECR Registry|1234567890.dkr.ecr.us-east-1.amazonaws.com|DEV/PROD|
|MAUTIC_CORS_DOMAINS            |Cross-Origin Resource Sharing, name the domains you want to allow to send tracking pixel and form requests to your Mautic instance. Accepts comma-separated values.|https://mydomain.com\,https://www.mydomain.com\,https://store.mydomain.com|DEV/PROD|
|MAUTIC_REPOSITORY_URL          |URL for your PHP-FPM Image in your registry.|1234567890.dkr.ecr.us-east-1.amazonaws.com/mautic-php-fpm|DEV/PROD|
|NGINX_REPOSITORY_URL           |URL for your Nginx Image in your registry.|1234567890.dkr.ecr.us-east-1.amazonaws.com/mautic-nginx|DEV/PROD|
|PROD_APP_SECRET_KEY            |Mautic Application Secret Key  |                                   |DEV|
|PROD_GA_TRACKING_ID            |Google Analytics Tracking Code |UA-123456789-1                     |DEV|
|PROD_INGRESS_DOMAIN            |Ingress Domain for K8s. Typically the TLD if mautic production is a subdomain|mydomain.com|DEV|
|PROD_MAILER_REGION             |Amazon Mailer Region (full URL)|email-smtp.us-east-1.amazonaws.com |DEV|
|PROD_MAILER_SECRET_KEY         |Amazon SES IAM Secret Key      |awsIamSecretKey                    |DEV|
|PROD_MAILER_USER               |Amazon SES IAM User            |awsIamUser                         |DEV|
|PROD_SAML_IDP_IDENTITY_ID      |Typically the hostname of your marketing domain|https://www.mydomain.com|DEV|
|RDS_HOST                       |Amazon RDS Hostname (URL)      |instance.hashString.us-east-1.rds.amazonaws.com|DEV/PROD|
|RDS_PASSWORD                   |Amazon RDS Password            |password                           |DEV/PROD|
|RDS_ROOT_PASSWORD              |Amazon RDS Root Password       |rootPassword                       |DEV/PROD|
|REGISTRY_AWS_REGION            |Amazon Region for ECR          |us-east-1                          |DEV/PROD|
|TRUSTED_PROXIES                |Upstream trusted proxies IP range|10.0.0.0/8\,172.16.0.0/12        |DEV/PROD|

_**Comma Separated Values must be escaped**_

## Environment Variables - _@TODO_ 

1. In the future it would be better to use GitLab environment stages instead of switching our variable naming convention.
2. This variable set and instructions are opinionated towards AWS SES. It would be great if we can set up a conditional statement for the `.Values.project.mailerHost` based on the selection of a transactional email service. 
3. Refactor `ECR_REGISTRY` to optionally work with any registry. 

# Contributors

[Facet Interactive](https://facetinteractive.com/services/mautic-development-managed-services?utm_source=mautic-k8s&utm_medium=github&utm_campaign=README.md) leads and sponsors the `mautic-k8s` project to support enterprise deployments of Mautic on Kubernetes. 

& special thanks to: 

- [Axelerant](https://axelerant.com/?utm_source=mautic-k8s&utm_medium=github&utm_campaign=README.md) for Helmizing Mautic and setting up GitLab CI/CD.

## Contributing

If you would like to get involved, please: 

* **Open an Issue** to let others know what you plan to work on.
* **Join the [#kubernetes](https://mautic.slack.com/archives/C01G6LHLM5M) channel in Mautic Slack**. 
* **Submit a Pull Request**. Don't forget to update the CHANGELOG.md with a summary of your changes. 

# License