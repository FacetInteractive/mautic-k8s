# Infrastructure Architecture

This document describes the architecture of the Mautic application deployed for Facet.

## Components:

- Source Code Repository      -  Gitlab  (Managed Instance)
- CI - CD Platform            -  Gitlab
- Container Registry          -  Elastic Container Registry
- Kubernetes                  -  EKS  - 5 Worker Nodes 
- Database                    -  MySQL running on RDS Instance 
- Storage                     -  EFS Filesystem for Persistent Volumes.



## Tools:

- Terraform     -   Provision infrastructure
- helm          -   Package Manager for Kubernetes
- gitlabci      -   Declarative CI-CD Pipeline



## Infrastructure Provisioning


[**Terraform**](https://terraform.io) is used to provision infrastructure for Facet Mautic. 

The configuration to provision the necessary infrastructure for Facet Mautic Application can be found here:  [mautic-docker-infra](https://gitlab.com/facet-interactive/bv/mautic-docker)




## CI-CD

gitlabci is used to created declarative CI-CD pipeline to build and deploy mautic application. The pipeline uses a Multi-dev deployment model: A namespace in Kubernetes cluster is created for each branch that is committed to the source code repository and a sub-domain with the branch name is created. This makes testing the features/bug fixes easy before merging with the main branch. 

There are 5 jobs/stages in this pipeline.
- dev-build
- prod-build
- dev-deploy
- prod-deploy
- cleanup

dev-build :
Builds the Docker images for Nginx and Mautic App. This step is triggered when developers check in code from a Non-master branch and pushed to the Container Registry (ECR) with the `CI_COMMIT_SHORT_SHA`as image tag.


dev-deploy:
Deploys the latest container images into the respective namespace after succesful completion of `dev-build` job. This job creates a new namespace with the branch name if one doesn't exist and helm deploys the application in the namespace. 

prod-build:
Builds the Docker images for Nginx and Mautic App. This step is triggered when developers check in code from master branch and pushed to the Container Registry (ECR) with the `latest`as image tag.


prod-deploy:
Deploys the latest container images into the `mautic-prod` namespace after succesful completion of `prod-build` job. This job creates a namespace called `mautic-prod`  if one doesn't exist and helm deploys the application in that namespace. 