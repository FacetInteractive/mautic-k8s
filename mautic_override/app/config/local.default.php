<?php

/**
 * @file - local.default.php - Copy this file to `local.php`
 * @description - This file is intentionally left blank in order to allow for
 *                $parameters_local.php configuration to be the definitive
 *                configuration file.
 *
 *                If Mautic is deployed with local.php set as writable,
 *                a copy of the configuration in parameters_local.php is
 *                written into local.php, and then parameters_local.php
 *                is no longer honored as the primary config.
 *
 *                If you'd like, you can optionally put your database configuration
 *                in this file, or into a config map mapped to this path.
 *
 * @var $parameters - defines the Mautic application configuration.
 */

$parameters = [];