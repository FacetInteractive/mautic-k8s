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

class ProviderContainer implements ProviderContainerInterface
{
    /** @var AttributeValueProviderInterface */
    private $attributeValueProvider;

    /** @var SessionInfoProviderInterface */
    private $sessionInfoProvider;

    /** @var NameIdProviderInterface */
    private $nameIdProvider;

    /**
     * @param AttributeValueProviderInterface $attributeValueProvider
     * @param SessionInfoProviderInterface    $sessionInfoProvider
     * @param NameIdProviderInterface         $nameIdProvider
     */
    public function __construct(
        AttributeValueProviderInterface $attributeValueProvider,
        SessionInfoProviderInterface $sessionInfoProvider,
        NameIdProviderInterface $nameIdProvider
    ) {
        $this->attributeValueProvider = $attributeValueProvider;
        $this->sessionInfoProvider = $sessionInfoProvider;
        $this->nameIdProvider = $nameIdProvider;
    }

    /**
     * @return AttributeValueProviderInterface
     */
    public function getAttributeValueProvider()
    {
        return $this->attributeValueProvider;
    }

    /**
     * @return SessionInfoProviderInterface
     */
    public function getSessionInfoProvider()
    {
        return $this->sessionInfoProvider;
    }

    /**
     * @return NameIdProviderInterface
     */
    public function getNameIdProvider()
    {
        return $this->nameIdProvider;
    }
}
