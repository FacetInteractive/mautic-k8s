<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Factory;

use LightSaml\Credential\CredentialInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use LightSaml\SymfonyBridgeBundle\Factory\CredentialStoreFactory;
use PHPUnit\Framework\TestCase;

class CredentialStoreFactoryTest extends TestCase
{
    public function test_returns_credential_store()
    {
        $factory = new CredentialStoreFactory();

        $credentialStoreMock = $this->getMockBuilder(CredentialStoreInterface::class)->getMock();
        $credentialStoreMock->method('getByEntityId')
            ->willReturn([$this->getMockBuilder(CredentialInterface::class)->getMock()]);

        $value = $factory->build(
            $this->getMockBuilder(EntityDescriptorStoreInterface::class)->getMock(),
            $this->getMockBuilder(EntityDescriptorStoreInterface::class)->getMock(),
            'own-id',
            $credentialStoreMock
        );

        $this->assertInstanceOf(CredentialStoreInterface::class, $value);
    }
}
