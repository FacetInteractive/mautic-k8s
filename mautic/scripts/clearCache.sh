#!/bin/sh
# Clear the Cache in Mautic
# Back, back, Back it up-ahhh
sh backup.sh
# Clear the cache
php app/console cache:clear
echo "Caches cleared!"
