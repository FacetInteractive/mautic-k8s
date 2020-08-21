<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use LightSaml\Store\TrustOptions\TrustOptionsStoreInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\PartyContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class PartyContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_idp_entity_descriptor_store()
    {
        $container = new PartyContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.party.idp_entity_descriptor_store')
            ->willReturn($expected = $this->getMock(EntityDescriptorStoreInterface::class));

        $this->assertSame($expected, $container->getIdpEntityDescriptorStore());
    }

    public function test_returns_sp_entity_descriptor_store()
    {
        $container = new PartyContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.party.sp_entity_descriptor_store')
            ->willReturn($expected = $this->getMock(EntityDescriptorStoreInterface::class));

        $this->assertSame($expected, $container->getSpEntityDescriptorStore());
    }

    public function test_returns_trust_options_store()
    {
        $container = new PartyContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.party.trust_options_store')
            ->willReturn($expected = $this->getMock(TrustOptionsStoreInterface::class));

        $this->assertSame($expected, $container->getTrustOptionsStore());
    }
}
