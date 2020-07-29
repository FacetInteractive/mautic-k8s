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

use LightSaml\Build\Container\PartyContainerInterface;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use LightSaml\Store\TrustOptions\TrustOptionsStoreInterface;

class PartyContainer implements PartyContainerInterface
{
    /** @var EntityDescriptorStoreInterface */
    private $idpEntityDescriptorStore;

    /** @var EntityDescriptorStoreInterface */
    private $spEntityDescriptorStore;

    /** @var TrustOptionsStoreInterface */
    private $trustOptionsStore;

    /**
     * @param EntityDescriptorStoreInterface $idpEntityDescriptorStore
     * @param EntityDescriptorStoreInterface $spEntityDescriptorStore
     * @param TrustOptionsStoreInterface     $trustOptionsStore
     */
    public function __construct(
        EntityDescriptorStoreInterface $idpEntityDescriptorStore,
        EntityDescriptorStoreInterface $spEntityDescriptorStore,
        TrustOptionsStoreInterface $trustOptionsStore
    ) {
        $this->idpEntityDescriptorStore = $idpEntityDescriptorStore;
        $this->spEntityDescriptorStore = $spEntityDescriptorStore;
        $this->trustOptionsStore = $trustOptionsStore;
    }

    /**
     * @return EntityDescriptorStoreInterface
     */
    public function getIdpEntityDescriptorStore()
    {
        return $this->idpEntityDescriptorStore;
    }

    /**
     * @return EntityDescriptorStoreInterface
     */
    public function getSpEntityDescriptorStore()
    {
        return $this->spEntityDescriptorStore;
    }

    /**
     * @return TrustOptionsStoreInterface
     */
    public function getTrustOptionsStore()
    {
        return $this->trustOptionsStore;
    }
}
