<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Provider\Attribute\AttributeValueProviderInterface;
use LightSaml\Provider\NameID\NameIdProviderInterface;
use LightSaml\Provider\Session\SessionInfoProviderInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\ProviderContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class ProviderContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_attribute_value_provider()
    {
        $container = new ProviderContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.provider.attribute_value')
            ->willReturn($expected = $this->getMock(AttributeValueProviderInterface::class));

        $this->assertSame($expected, $container->getAttributeValueProvider());
    }

    public function test_returns_session_info_provider()
    {
        $container = new ProviderContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.provider.session_info')
            ->willReturn($expected = $this->getMock(SessionInfoProviderInterface::class));

        $this->assertSame($expected, $container->getSessionInfoProvider());
    }

    public function test_returns_name_id_provider()
    {
        $container = new ProviderContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.provider.name_id')
            ->willReturn($expected = $this->getMock(NameIdProviderInterface::class));

        $this->assertSame($expected, $container->getNameIdProvider());
    }
}
