#!/bin/sh

TIMESTAMP=$(date +%Y-%m-%d_%H%M%S)
# @TODO - Make this use ENV variables for file naming, e.g. $CLOUD,$PROJECT,$STAGE
# @TODO - Check for existence of each of the $PROD_* environment variables and exit if they are not available.

set -e

# Using environment variables,
# Download a copy of the Live MySQL Database
mysqldump -h ${PROD_DB_HOST} \
          -u ${PROD_DB_USER} \
          -p${PROD_DB_PASS} \
          --port=${PROD_DB_PORT} \
          ${PROD_DB_NAME} \
          --single-transaction \
          --routines \
          --triggers \
          --compress \
          --set-gtid-purged=OFF | \
          pv --progress -r -b -t -w 60 | gzip -c > /tmp/db-init/aws_facet_mautic_live-dump-${TIMESTAMP}.sql.gz

echo "A copy of the AWS Facet Mautic Live MySQL database is available at [db-init/aws_facet_mautic_live-dump-${TIMESTAMP}.sql.gz]."
echo "Please import the file using [lando db-import]."
