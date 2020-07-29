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

class BuildContainer implements BuildContainerInterface
{
    /** @var SystemContainerInterface */
    private $systemsystemContainer;

    /** @var PartyContainerInterface */
    private $partypartyContainer;

    /** @var StoreContainerInterface */
    private $storeContainer;

    /** @var OwnContainerInterface */
    private $ownContainer;

    /** @var ProviderContainerInterface */
    private $providerContainer;

    /** @var ServiceContainerInterface */
    private $serviceContainer;

    /** @var CredentialContainerInterface */
    private $credentialContainer;

    /**
     * @param SystemContainerInterface     $systemContainer
     * @param PartyContainerInterface      $partyContainer
     * @param StoreContainerInterface      $storeContainer
     * @param ProviderContainerInterface   $providerContainer
     * @param CredentialContainerInterface $credentialContainer
     * @param ServiceContainerInterface    $serviceContainer
     * @param OwnContainerInterface        $ownContainer
     */
    public function __construct(
        SystemContainerInterface $systemContainer,
        PartyContainerInterface $partyContainer,
        StoreContainerInterface $storeContainer,
        ProviderContainerInterface $providerContainer,
        CredentialContainerInterface $credentialContainer,
        ServiceContainerInterface $serviceContainer,
        OwnContainerInterface $ownContainer
    ) {
        $this->systemsystemContainer = $systemContainer;
        $this->partypartyContainer = $partyContainer;
        $this->storeContainer = $storeContainer;
        $this->providerContainer = $providerContainer;
        $this->credentialContainer = $credentialContainer;
        $this->serviceContainer = $serviceContainer;
        $this->ownContainer = $ownContainer;
    }

    /**
     * @return SystemContainerInterface
     */
    public function getSystemContainer()
    {
        return $this->systemsystemContainer;
    }

    /**
     * @return PartyContainerInterface
     */
    public function getPartyContainer()
    {
        return $this->partypartyContainer;
    }

    /**
     * @return StoreContainerInterface
     */
    public function getStoreContainer()
    {
        return $this->storeContainer;
    }

    /**
     * @return ProviderContainerInterface
     */
    public function getProviderContainer()
    {
        return $this->providerContainer;
    }

    /**
     * @return CredentialContainerInterface
     */
    public function getCredentialContainer()
    {
        return $this->credentialContainer;
    }

    /**
     * @return ServiceContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }

    /**
     * @return OwnContainerInterface
     */
    public function getOwnContainer()
    {
        return $this->ownContainer;
    }
}
