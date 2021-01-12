#!/bin/sh

TIMESTAMP=$(date +%Y-%m-%d_%H:%M:%S)

set -e

cd "$(dirname "$0")/.."

echo "Resetting the Admin Password for user ID 1..."
# HOST may not be necessary since this is running locally in lando container.
mysql -h ${MYSQL_DB_HOST} \
      -u ${MYSQL_USER} \
      -p${MYSQL_PASSWORD} <<EOF
UPDATE ${MYSQL_DATABASE}.users SET password = "\$2a\$04\$LrQYZmEMFi7GghF0EIv4FOdNv8bFcnlXM9Bta0eb8BWLLlRwcKrUm" where id = 1;
EOF

printf "Password for uid=1 has been reset to 'secret'\n"