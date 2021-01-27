# Mautic-k8s EFK 

EFK stack is not included in our Mautic Helm Chart, and so you need to set up ELK separately.

The instructions below demonstrate how to update Filestream and Fluentd to consume the appropriate logs.

First the basics, logging in.

## How to View Logs

Login to Kibana with `kubectl` and port forwarding.

```bash
kubectl -n logging port-forward service/efk-kibana 9100:443
``` 

Then on your browser `localhost:9100`.

## How to update Logs Path

```bash
kubectl get cm filebeat-filebeat-config -n elk -o yaml
```

Output should look something like this in a default Filebeat setup:

```yaml
apiVersion: v1
data:
  filebeat.yml: |
    filebeat.inputs:
    - type: container
      paths:
        - /var/log/containers/*.log
      processors:
      - add_kubernetes_metadata:
          host: ${NODE_NAME}
          matchers:
          - logs_path:
              logs_path: "/var/log/containers/"

    output.elasticsearch:
      host: '${NODE_NAME}'
      hosts: '${ELASTICSEARCH_HOSTS:elasticsearch-master:9200}'
kind: ConfigMap
metadata:
  annotations:
    meta.helm.sh/release-name: filebeat
    meta.helm.sh/release-namespace: elk
  creationTimestamp: "2020-10-29T15:16:08Z"
  labels:
    app: filebeat-filebeat
    app.kubernetes.io/managed-by: Helm
    chart: filebeat-7.9.3
    heritage: Helm
    release: filebeat
  name: filebeat-filebeat-config
  namespace: elk
  resourceVersion: "32445660"
  selfLink: /api/v1/namespaces/elk/configmaps/filebeat-filebeat-config
  uid: 64034d81-2dd0-45f8-aa65-b3338dc51034
```

This essentially says that it will look for files in `/var/log/containers/*.log` pattern.

`@TODO - How to add Mautic specific paths and provide an example here`
