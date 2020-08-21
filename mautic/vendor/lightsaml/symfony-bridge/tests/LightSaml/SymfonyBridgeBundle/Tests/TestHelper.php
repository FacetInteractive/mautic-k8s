<?php

namespace LightSaml\SymfonyBridgeBundle\Tests;

abstract class TestHelper
{
    /**
     * @param \PHPUnit_Framework_TestCase $test
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    public static function getContainerMock(\PHPUnit_Framework_TestCase $test)
    {
        return $test->getMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    }
}
