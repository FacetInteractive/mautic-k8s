#!/bin/sh

TIMESTAMP=$(date +%Y-%m-%d_%H:%M:%S)

set -e

cd "$(dirname "$0")/.."

mysqldump -h ${PROD_DB_HOST} \
    -u ${PROD_DB_USER} \
    -p${PROD_DB_PASSWORD} \
    --port=${PROD_DB_PORT} \
    --single-transaction \
    --routines \
    --triggers \
    --databases ${PROD_DB_NAME} \
    --compress \
    --result-file=$(dirname "$0")/../_aws_facet_mautic_live-${TIMESTAMP}-dump.sql