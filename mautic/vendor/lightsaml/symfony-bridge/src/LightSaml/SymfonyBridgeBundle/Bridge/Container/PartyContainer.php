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

class PartyContainer extends AbstractContainer implements PartyContainerInterface
{
    /**
     * @return EntityDescriptorStoreInterface
     */
    public function getIdpEntityDescriptorStore()
    {
        return $this->container->get('lightsaml.party.idp_entity_descriptor_store');
    }

    /**
     * @return EntityDescriptorStoreInterface
     */
    public function getSpEntityDescriptorStore()
    {
        return $this->container->get('lightsaml.party.sp_entity_descriptor_store');
    }

    /**
     * @return TrustOptionsStoreInterface
     */
    public function getTrustOptionsStore()
    {
        return $this->container->get('lightsaml.party.trust_options_store');
    }
}
