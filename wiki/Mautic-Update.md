# How to Update Mautic

In order to update Mautic core, we need to run a simple composer command.

1. First, find the git commit hash ID from GitHub. We _cannot_ use release tags to build, dependably.
2. `composer require mautic/core:dev-master#{someHashId}`

After running this command, simply commit the updated `composer.json` and `composer.lock` files. 

The deployment will automatically install the updated packages and run `migration` commands on post deploy. 