#!/bin/sh
cd /var/www/symfony
composer migrate
composer installplugins
mautic/app/composer cache:clear --env=prod
# @TODO - re-enable, failing in production
# rm -rf /cache/{pro_,prod,run}
until mautic/app/console cache:warmup ; do sleep 5; done; echo "OKAY"

