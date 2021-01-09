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

- [ ] Upgrade to Mautic 2.16.4
- [ ] Upgrade to PHP 7.3
- [ ] Confirm Redis Connection in PHP 7.3 for Shared Sessions
- [ ] Branch to support Mautic 3.x / PHP 7.4

# Local Mautic Dev With Lando

For instructions on how to set up `mautic-k8s` with Lando, [see the documentation here](./wiki/local-dev-getting-started.md)

# K8s Setup

For instructions 

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