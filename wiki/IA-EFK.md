# Mautic-k8s EFK 

## View Logs

Login to Kibana with `kubectl` and port forwarding.

```
kubectl -n logging port-forward service/efk-kibana 9100:443
``` 

Then on your browser `localhost:9100`.