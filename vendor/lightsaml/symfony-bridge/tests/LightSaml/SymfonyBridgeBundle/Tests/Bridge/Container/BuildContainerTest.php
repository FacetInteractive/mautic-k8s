<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Build\Container\CredentialContainerInterface;
use LightSaml\Build\Container\OwnContainerInterface;
use LightSaml\Build\Container\PartyContainerInterface;
use LightSaml\Build\Container\ProviderContainerInterface;
use LightSaml\Build\Container\ServiceContainerInterface;
use LightSaml\Build\Container\StoreContainerInterface;
use LightSaml\Build\Container\SystemContainerInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\BuildContainer;
use PHPUnit\Framework\TestCase;

class BuildContainerTest extends TestCase
{
    public function test_constructs_with_all_containers()
    {
        new BuildContainer(
            $this->getMockBuilder(SystemContainerInterface::class)->getMock(),
            $this->getMockBuilder(PartyContainerInterface::class)->getMock(),
            $this->getMockBuilder(StoreContainerInterface::class)->getMock(),
            $this->getMockBuilder(ProviderContainerInterface::class)->getMock(),
            $this->getMockBuilder(CredentialContainerInterface::class)->getMock(),
            $this->getMockBuilder(ServiceContainerInterface::class)->getMock(),
            $this->getMockBuilder(OwnContainerInterface::class)->getMock()
        );
    }
}
