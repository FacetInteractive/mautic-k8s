# How to Download Database from RDS

In order to encapsulate the `mysqldump` process, we've written a bash script and can trigger it programmatically from within a lando Docker container.

MySQL Script can be found at: 
```
/scripts/pull_live_db.sh
```

Lando Command: 
```
lando pull-live-db
```

## Requirements

1. `.env` must be set up with production credentials in order to connect to the PROD or another SOURCE database.

- PROD_DB_HOST
- PROD_DB_PORT 
- PROD_DB_USER
- PROD_DB_PASS
- PROD_DB_NAME