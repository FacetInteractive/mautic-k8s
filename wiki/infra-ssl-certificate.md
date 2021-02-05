# Update SSL certificate


## Overview
This documentation covers on how to update an existing SSL certificate in Kubernetes using Lets Encrypt generated certificate.


## Before you start
Make sure you meet the following prerequisites before starting the how-to steps:
*The domain is hosted in Route53, and AWS credentials are present. Certbot uses these credentials to update TXT records in the hosted zone.
* [certbot] (https://certbot.eff.org/docs/install.html#) is installed
  To generate wild card certificate,
* [Certbot-Route53](https://pypi.org/project/certbot-dns-route53/#description) is installed for the DNS-01 challenge.
* Kubectl is installed and kubeconfig is present on the machine.


## Step-by-step guide

### Step 1: Generate/Renew the certificates using Lets encrypt
Run the following command on your machine, Mention the directory location wherer the certificates needs to generated.
```bash
certbot certonly -d '*.facetinteractive.com' --dns-route53 -m <EMAIL_ADDRESS> --agree-tos --server https://acme-v02.api.letsencrypt.org/directory --logs-dir <DIRECTORY> --config-dir <DIRECTORY> --work-dir <DIRECTORY>
```

### Step 2: Update the Kuberetes Secret
* The current ingress controller is under the mautic-prod namespace.
* Inorder to update the secret, you have to first delete it and recreate it.
* To delete the secret,

```bash
kubectl delete secret mautic-master-traefik-default-cert -n mautic-prod
```

* Under <WORKING_DIR>/live/<SITE_NAME>, you can find the certificates generated.
* To create the secret with latest TLS certificates, change the directory to the previously mentioned one. 

```bash  
kubectl -n mautic-prod create secret tls mautic-master-traefik-default-cert  --cert=fullchain.pem --key=privkey.pem
```