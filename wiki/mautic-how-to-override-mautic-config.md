# How to Customize Mautic

There are two ways to override root Mautic project files:

- Patch Mautic Files
- Symlink Files inside appropriate Mautic Directory

## Patch Mautic Files

Create a patch file and programmatically patch the Mautic project. _Recommended for small changes and bug fixes to pre-existing files._

## Symlink via /mautic_overrides

Create a file in `/mautic_overrides` which contains _full copies of files_ to be symlinked inside of the relative `/mautic` path. 

 - For example, `/mautic_overrides/app/config/parameters_local.php` is the file where we specify the Mautic application configuration settings.
 
- We use this set up to allow Composer to programmatically install each time the Docker image builds, and rather than _copying_ the configuration file or using a config map, instead we symlink the file programmatically based on hierarchy. 

## Install Third-Party Mautic Plugins via composer.custom

To programmatically install a third-party Mautic plugin, simply specific the plugin in `composer.custom`, which will be included during the `composer install`.

For example:

```json
{
    "require": {
      "logicify/mautic-advanced-templates-bundle": "master"
    },
    "config": {
        "preferred-install": {
            "*": "dist"
        }
    },
    "repositories": [
    ]
}
```