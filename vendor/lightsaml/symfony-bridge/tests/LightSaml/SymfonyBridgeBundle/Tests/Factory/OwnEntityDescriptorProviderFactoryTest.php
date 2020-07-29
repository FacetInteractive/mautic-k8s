<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Factory;

use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509CredentialInterface;
use LightSaml\Provider\EntityDescriptor\EntityDescriptorProviderInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\SymfonyBridgeBundle\Factory\OwnEntityDescriptorProviderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class OwnEntityDescriptorProviderFactoryTest extends TestCase
{
    public function test_returns_entity_descriptor_builder()
    {
        $factory = new OwnEntityDescriptorProviderFactory();

        $routerMock = $this->getMockBuilder(RouterInterface::class)->getMock();
        $routerMock->expects($this->exactly(2))
            ->method('generate')
            ->with($this->isType('string'), [], RouterInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost');

        $credentialStoreMock = $this->getMockBuilder(CredentialStoreInterface::class)->getMock();
        $credentialStoreMock->method('getByEntityId')
            ->with($ownEntityId = 'own-id')
            ->willReturn([$credentialMock = $this->getMockBuilder(X509CredentialInterface::class)->getMock()]);

        $credentialMock->method('getCertificate')
            ->willReturn($this->getMockBuilder(X509Certificate::class)->getMock());

        $value = $factory->build(
            $ownEntityId,
            $routerMock,
            'acs',
            'sso',
            $credentialStoreMock
        );

        $this->assertInstanceOf(EntityDescriptorProviderInterface::class, $value);
    }
}
