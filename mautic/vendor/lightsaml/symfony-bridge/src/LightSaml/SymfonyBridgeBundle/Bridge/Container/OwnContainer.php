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

use LightSaml\Build\Container\OwnContainerInterface;
use LightSaml\Credential\CredentialInterface;
use LightSaml\Provider\EntityDescriptor\EntityDescriptorProviderInterface;

class OwnContainer extends AbstractContainer implements OwnContainerInterface
{
    /**
     * @return EntityDescriptorProviderInterface
     */
    public function getOwnEntityDescriptorProvider()
    {
        return $this->container->get('lightsaml.own.entity_descriptor_provider');
    }

    /**
     * @return CredentialInterface[]
     */
    public function getOwnCredentials()
    {
        return $this->container->get('lightsaml.own.credential_store')->getByEntityId(
            $this->container->getParameter('lightsaml.own.entity_id')
        );
    }
}
