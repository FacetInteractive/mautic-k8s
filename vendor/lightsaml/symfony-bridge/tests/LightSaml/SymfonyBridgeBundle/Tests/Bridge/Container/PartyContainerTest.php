<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use LightSaml\Store\TrustOptions\TrustOptionsStoreInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\PartyContainer;
use PHPUnit\Framework\TestCase;

class PartyContainerTest extends TestCase
{
    public function test_constructs_with_all_arguments()
    {
        new PartyContainer(
            $this->getMockBuilder(EntityDescriptorStoreInterface::class)->getMock(),
            $this->getMockBuilder(EntityDescriptorStoreInterface::class)->getMock(),
            $this->getMockBuilder(TrustOptionsStoreInterface::class)->getMock()
        );
    }
}
