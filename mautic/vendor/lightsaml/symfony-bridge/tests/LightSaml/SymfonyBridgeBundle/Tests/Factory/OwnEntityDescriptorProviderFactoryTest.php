<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Factory;

use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509CredentialInterface;
use LightSaml\Provider\EntityDescriptor\EntityDescriptorProviderInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\SymfonyBridgeBundle\Factory\OwnEntityDescriptorProviderFactory;
use Symfony\Component\Routing\RouterInterface;

class OwnEntityDescriptorProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_entity_descriptor_builder()
    {
        $factory = new OwnEntityDescriptorProviderFactory();

        $routerMock = $this->getMock(RouterInterface::class);
        $routerMock->expects($this->exactly(2))
            ->method('generate')
            ->with($this->isType('string'), [], RouterInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost');

        $credentialStoreMock = $this->getMock(CredentialStoreInterface::class);
        $credentialStoreMock->method('getByEntityId')
            ->with($ownEntityId = 'own-id')
            ->willReturn([$credentialMock = $this->getMock(X509CredentialInterface::class)]);

        $credentialMock->method('getCertificate')
            ->willReturn($this->getMock(X509Certificate::class));

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
