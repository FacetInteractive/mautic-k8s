<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\SymfonyBridgeBundle\Bridge\Container\AbstractContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;

class AbstractContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_container()
    {
        $this->getMockForAbstractClass(AbstractContainer::class, [TestHelper::getContainerMock($this)]);
    }
}
