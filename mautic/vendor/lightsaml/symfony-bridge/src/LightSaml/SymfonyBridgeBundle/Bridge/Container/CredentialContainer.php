<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\Bridge\Container;

use LightSaml\Build\Container\CredentialContainerInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;

class CredentialContainer extends AbstractContainer implements CredentialContainerInterface
{
    /**
     * @return CredentialStoreInterface
     */
    public function getCredentialStore()
    {
        return $this->container->get('lightsaml.credential.credential_store');
    }
}
