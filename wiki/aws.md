# AWS Documentation

## Getting Started

### Required AWS Services

- EKS - Elastic Container Service for Kubernetes
- ECS - Elastic Container Service (required by EKS)
- ECR - Elastic Container Registry
- RDS - Relational Database Service

### Required for AWS Setup

* Have [AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html) installed and configured.
    * Set up a `profile` for your AWS credentials `~/.aws/credentials`
    * Specify your AWS `region` in your local AWS profile `~/.aws/config`

### Login to AWS ECR

```bash
# Request ECR Login
aws ecr get-login --no-include-email

# With a profile
aws --profile {aws.mautic.profile} ecr get-login --no-include-email
```

** Note that {aws.mautic.profile} should be the name of the AWS profile you set up in `.aws`

### Create Mautic and Nginx Registries on ECR

@TODO - Add steps to create registries.

### How to Manually Install mautic-k8s

To finish setting up your `mautic-k8s` project without GitLab CI/CD, use the [K8s EKS How to Manually Deploy documentation](./k8s-eks-how-to-manually-deploy.md). 