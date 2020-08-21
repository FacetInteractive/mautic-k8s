<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Factory;

use LightSaml\Credential\CredentialInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use LightSaml\SymfonyBridgeBundle\Factory\CredentialStoreFactory;

class CredentialStoreFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_credential_store()
    {
        $factory = new CredentialStoreFactory();

        $credentialStoreMock = $this->getMock(CredentialStoreInterface::class);
        $credentialStoreMock->method('getByEntityId')
            ->willReturn([$this->getMock(CredentialInterface::class)]);

        $value = $factory->build(
            $this->getMock(EntityDescriptorStoreInterface::class),
            $this->getMock(EntityDescriptorStoreInterface::class),
            'own-id',
            $credentialStoreMock
        );

        $this->assertInstanceOf(CredentialStoreInterface::class, $value);
    }
}
