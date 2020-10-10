#!/bin/sh
cd /var/www/symfony
composer post-deploy
rm -rf /cache/{pro_,prod,run}
until mautic/app/console cache:warmup ; do sleep 5; done; echo "OKAY"

