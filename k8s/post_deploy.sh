#!/bin/sh
cd /var/www/symfony/mautic
app/console doctrine:migrations:migrate --no-ansi --no-interaction
rm -rf /cache/{pro_,prod,run}
until app/console cache:warmup ; do sleep 5; done; echo "OKAY"

