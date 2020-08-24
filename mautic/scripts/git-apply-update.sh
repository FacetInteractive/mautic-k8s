#!/bin/sh

# Run this script after merging an update OR after you have run git-stage-update.sh
# Run this script from project root using `./scripts/git-apply-update.sh`
# Run from remote server
git fetch --all
git checkout staging
git pull origin staging

PHPPATH='/opt/plesk/php/7.0/bin/php'
COMPOSERPATH='/usr/local/bin/composer'

if [ -n "$PHPPATH" ]; then
  PHPPATH='php'
fi

if [ -n "$COMPOSERPATH" ]; then
  COMPOSERPATH='composer'
fi

# Install Composer Files
"$PHPPATH" "$COMPOSERPATH" install
# Clear Caches
rm -r app/cache/prod
# Run Symfony Doctrines
# NOTE: This assumes that you are updating from a tagged release to a tagged release
"$PHPPATH" app/console doctrine:migrations:migrate --env=prod
# If you are not updating from a tagged release, you will need alternative commands
# "PHPPATH" app/console doctrine:schema:update --env=prod --dump-sql
# Inspect the output from above, this is just a dry-run
# If you are satisfied with the queries, execute them with
# "$PHPPATH" app/console doctrine:schema:update --env=prod --force
