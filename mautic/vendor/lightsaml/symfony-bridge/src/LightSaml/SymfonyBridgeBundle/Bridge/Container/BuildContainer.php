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

use LightSaml\Build\Container\BuildContainerInterface;
use LightSaml\Build\Container\CredentialContainerInterface;
use LightSaml\Build\Container\OwnContainerInterface;
use LightSaml\Build\Container\PartyContainerInterface;
use LightSaml\Build\Container\ProviderContainerInterface;
use LightSaml\Build\Container\ServiceContainerInterface;
use LightSaml\Build\Container\StoreContainerInterface;
use LightSaml\Build\Container\SystemContainerInterface;

class BuildContainer extends AbstractContainer implements BuildContainerInterface
{
    /** @var AbstractContainer[] */
    private $containers = [];

    /**
     * @return SystemContainerInterface
     */
    public function getSystemContainer()
    {
        return $this->getContainer(SystemContainer::class);
    }

    /**
     * @return PartyContainerInterface
     */
    public function getPartyContainer()
    {
        return $this->getContainer(PartyContainer::class);
    }

    /**
     * @return StoreContainerInterface
     */
    public function getStoreContainer()
    {
        return $this->getContainer(StoreContainer::class);
    }

    /**
     * @return ProviderContainerInterface
     */
    public function getProviderContainer()
    {
        return $this->getContainer(ProviderContainer::class);
    }

    /**
     * @return CredentialContainerInterface
     */
    public function getCredentialContainer()
    {
        return $this->getContainer(CredentialContainer::class);
    }

    /**
     * @return ServiceContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->getContainer(ServiceContainer::class);
    }

    /**
     * @return OwnContainerInterface
     */
    public function getOwnContainer()
    {
        return $this->getContainer(OwnContainer::class);
    }

    /**
     * @param string $class
     *
     * @return AbstractContainer
     */
    private function getContainer($class)
    {
        if (false === isset($this->containers[$class])) {
            $this->containers[$class] = new $class($this->container);
        }

        return $this->containers[$class];
    }
}
