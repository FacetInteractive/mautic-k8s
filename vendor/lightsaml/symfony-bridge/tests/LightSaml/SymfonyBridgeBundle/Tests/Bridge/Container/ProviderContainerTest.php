<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Provider\Attribute\AttributeValueProviderInterface;
use LightSaml\Provider\NameID\NameIdProviderInterface;
use LightSaml\Provider\Session\SessionInfoProviderInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\ProviderContainer;
use PHPUnit\Framework\TestCase;

class ProviderContainerTest extends TestCase
{
    public function test_constructs_with_all_arguments()
    {
        new ProviderContainer(
            $this->getMockBuilder(AttributeValueProviderInterface::class)->getMock(),
            $this->getMockBuilder(SessionInfoProviderInterface::class)->getMock(),
            $this->getMockBuilder(NameIdProviderInterface::class)->getMock()
        );
    }
}
