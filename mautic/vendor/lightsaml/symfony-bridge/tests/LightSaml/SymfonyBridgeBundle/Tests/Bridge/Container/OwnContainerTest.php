<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Credential\CredentialInterface;
use LightSaml\Provider\EntityDescriptor\EntityDescriptorProviderInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\OwnContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class OwnContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_own_entity_provider()
    {
        $container = new OwnContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.own.entity_descriptor_provider')
            ->willReturn($expected = $this->getMock(EntityDescriptorProviderInterface::class));

        $this->assertSame($expected, $container->getOwnEntityDescriptorProvider());
    }

    public function test_returns_own_credentials()
    {
        $container = new OwnContainer($containerMock = TestHelper::getContainerMock($this));

        $containerMock->method('get')
            ->with('lightsaml.own.credential_store')
            ->willReturn($credentialStoreMock = $this->getMock(CredentialStoreInterface::class));
        $containerMock->method('getParameter')
            ->with('lightsaml.own.entity_id')
            ->willReturn('foo');

        $credentialStoreMock->method('getByEntityId')
            ->willReturn([$expected = $this->getMock(CredentialInterface::class)]);

        $result = $container->getOwnCredentials();
        $this->assertTrue(is_array($result));
        $this->assertSame($expected, $result[0]);
    }
}
