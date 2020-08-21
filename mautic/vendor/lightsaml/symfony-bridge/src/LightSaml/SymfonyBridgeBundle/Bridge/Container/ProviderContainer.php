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

use LightSaml\Build\Container\ProviderContainerInterface;
use LightSaml\Provider\Attribute\AttributeValueProviderInterface;
use LightSaml\Provider\NameID\NameIdProviderInterface;
use LightSaml\Provider\Session\SessionInfoProviderInterface;

class ProviderContainer extends AbstractContainer implements ProviderContainerInterface
{
    /**
     * @return AttributeValueProviderInterface
     */
    public function getAttributeValueProvider()
    {
        return $this->container->get('lightsaml.provider.attribute_value');
    }

    /**
     * @return SessionInfoProviderInterface
     */
    public function getSessionInfoProvider()
    {
        return $this->container->get('lightsaml.provider.session_info');
    }

    /**
     * @return NameIdProviderInterface
     */
    public function getNameIdProvider()
    {
        return $this->container->get('lightsaml.provider.name_id');
    }
}
