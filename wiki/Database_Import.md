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
