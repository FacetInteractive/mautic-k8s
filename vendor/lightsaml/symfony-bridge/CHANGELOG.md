# Changelog

## 1.3.0 2018-05-23

* profile services made public, so they are not automatically removed and that they can be get from container

## 1.2.0 2018-05-23

* Braking change - `lightsaml.system.logger` no longer defaults to alias of `logger`. It must be specified in 
  `light_saml_symfony_bridge.system.logger` config, or otherwise it defaults to `Psr\Log\NullLogger` 
* Braking change - Minimum Symfony version supported moved to 2.7
