# K8s Kubernetes

## Troubleshooting Failed Deployments

If for any reason deployment fails, particularly when it says something like below

```
Error: UPGRADE FAILED: "mautic-dev" has no deployed releases
``` 

run

```bash
helm delete --purge <release_name>
kubectl delete <namesapce
```

If available run the cleanup job manually which basically does the same as above.

To find the release name and corresponding namespace, run

```bash
helm ls
```

You'd need helm installed on your machine.

For the DNS entries, the deploy stage itself gives instructions to find the ELB DNS Name:

1. Get the application URL by running these command:

```bash
kubectl get ingress mautic-mautic-composer-3-hello-ingress  --namespace mautic-composer-3 -o jsonpath='{.spec.rules[*].host}'

```

2. Get the ELB DNS record by running the below command:
```bash
kubectl get svc mautic-mautic-composer-3-traefik --namespace mautic-composer-3 -o jsonpath='{.status.loadBalancer.ingress[*].hostname}'
```
