<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\SymfonyBridgeBundle\Bridge\Container\BuildContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\CredentialContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\OwnContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\PartyContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\ProviderContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\ServiceContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\StoreContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\SystemContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class BuildContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_container()
    {
        new BuildContainer(TestHelper::getContainerMock($this));
    }

    public function test_returns_system_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(SystemContainer::class, $buildContainer->getSystemContainer());
    }

    public function test_returns_party_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(PartyContainer::class, $buildContainer->getPartyContainer());
    }

    public function test_returns_store_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(StoreContainer::class, $buildContainer->getStoreContainer());
    }

    public function test_returns_provider_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(ProviderContainer::class, $buildContainer->getProviderContainer());
    }

    public function test_returns_credential_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(CredentialContainer::class, $buildContainer->getCredentialContainer());
    }

    public function test_returns_service_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(ServiceContainer::class, $buildContainer->getServiceContainer());
    }

    public function test_returns_own_container()
    {
        $buildContainer = new BuildContainer(TestHelper::getContainerMock($this));
        $this->assertInstanceOf(OwnContainer::class, $buildContainer->getOwnContainer());
    }
}
