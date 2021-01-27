#!/bin/sh

TIMESTAMP=$(date +%Y-%m-%d_%H:%M:%S)

set -e

cd "$(dirname "$0")/.."

echo "Query to find corrupted rows, social_cache data which doesn't end with curly braces as any serialised object should."
# HOST may not be necessary since this is running locally in lando container.
COUNT_CORRUPT_ROWS = $(mysql -h ${MYSQL_DB_HOST} \
      -u ${MYSQL_USER} \
      -p${MYSQL_PASSWORD} <<EOF
USE ${MYSQL_DATABASE};
SELECT count(*) FROM leads WHERE leads.social_cache REGEXP '[^}]$';
EOF)
echo "$COUNT_CORRUPT_ROWS corrupted rows counted."

echo "Query to delete corrupted rows..."
mysql -h ${MYSQL_DB_HOST} \
      -u ${MYSQL_USER} \
      -p${MYSQL_PASSWORD} <<EOF
USE ${MYSQL_DATABASE};
DELETE from leads WHERE leads.social_cache REGEXP '[^}]$';
EOF

printf "Corrupted rows deleted.\n"