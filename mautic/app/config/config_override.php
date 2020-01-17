<?php

/**
 * @file - config_override.php
 *
 * Allow for overrides of config without requiring a bundle or hacks.
 *
 * Included in each of config_dev.php, config_test.php, and config_prod.php
 *
 * Consider config_local.php if implementing a solution for local development purposes.
 */

/**
 * Configure the S3 Media Plugin per environment.
 */

$container->setParameter('mautic.integration.keyfield.amazons3.bucket', getenv('S3_BUCKET'));
$container->setParameter('mautic.integration.keyfield.amazons3.clientid', getenv('S3_CLIENT_ID'));
$container->setParameter('mautic.integration.keyfield.amazons3.clientsecret', getenv('S3_CLIENT_SECRET'));

