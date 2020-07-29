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
use LightSaml\Store\Credential\CredentialStoreInterface;

class OwnContainer implements OwnContainerInterface
{
    /** @var EntityDescriptorProviderInterface */
    private $entityDescriptorProvider;

    /** @var CredentialStoreInterface */
    private $credentialStore;

    /** @var string */
    private $entityId;

    /**
     * @param EntityDescriptorProviderInterface $entityDescriptorProvider
     * @param CredentialStoreInterface          $credentialStore
     * @param string                            $entityId
     */
    public function __construct(
        EntityDescriptorProviderInterface $entityDescriptorProvider,
        CredentialStoreInterface $credentialStore,
        $entityId
    ) {
        $this->entityDescriptorProvider = $entityDescriptorProvider;
        $this->credentialStore = $credentialStore;
        $this->entityId = $entityId;
    }

    /**
     * @return EntityDescriptorProviderInterface
     */
    public function getOwnEntityDescriptorProvider()
    {
        return $this->entityDescriptorProvider;
    }

    /**
     * @return CredentialInterface[]
     */
    public function getOwnCredentials()
    {
        return $this->credentialStore->getByEntityId(
            $this->entityId
        );
    }
}
