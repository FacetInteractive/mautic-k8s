# How to Update Mautic

In order to update Mautic core, we need to run a simple composer command.

1. First, find the git commit hash ID from GitHub. We _cannot_ use release tags to build, dependably.
2. `composer require mautic/core:dev-master#{someHashId}`
3. `composer update --with-dependencies`

After running this command, simply commit the updated `composer.json` and `composer.lock` files. 

### When Running in Local

_when running these commands locally in development, you'll need to run additional migrations_:

```bash
composer migrate
composer installplugins
path/to/console cache:clear --env=dev
```

### When deploying updates to production

The deployment will automatically install the updated packages and run `migration` commands on post deploy using the commands in `/k8s/post_deploy.sh`.
