<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\CredentialContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class CredentialContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_credential_store()
    {
        $container = new CredentialContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.credential.credential_store')
            ->willReturn($expected = $this->getMock(CredentialStoreInterface::class));

        $this->assertSame($expected, $container->getCredentialStore());
    }
}
