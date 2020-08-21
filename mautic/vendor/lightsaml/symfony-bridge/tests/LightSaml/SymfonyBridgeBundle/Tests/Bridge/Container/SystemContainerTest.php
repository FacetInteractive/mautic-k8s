<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\Bridge\Container;

use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\SystemContainer;
use LightSaml\SymfonyBridgeBundle\Tests\TestHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SystemContainerTest extends \PHPUnit_Framework_TestCase
{
    public function test_returns_request_from_stack()
    {
        if (false == class_exists('Symfony\Component\HttpFoundation\RequestStack')) {
            return;
        }

        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('has')
            ->with('request_stack')
            ->willReturn(true);
        $containerMock->method('get')
            ->with('request_stack')
            ->willReturn($requestStackMock = $this->getMock(RequestStack::class));

        $requestStackMock->method('getCurrentRequest')
            ->willReturn($expected = new Request());

        $this->assertSame($expected, $container->getRequest());
    }

    public function test_returns_request_from_scope()
    {
        if (class_exists('Symfony\Component\HttpFoundation\RequestStack')) {
            return;
        }

        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('has')
            ->with('request_stack')
            ->willReturn(false);
        $containerMock->method('get')
            ->with('request')
            ->willReturn($expected = new Request());

        $this->assertSame($expected, $container->getRequest());
    }

    public function test_returns_session()
    {
        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('session')
            ->willReturn($expected = $this->getMock(SessionInterface::class));

        $this->assertSame($expected, $container->getSession());
    }

    public function test_returns_time_provider()
    {
        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.system.time_provider')
            ->willReturn($expected = $this->getMock(TimeProviderInterface::class));

        $this->assertSame($expected, $container->getTimeProvider());
    }

    public function test_returns_event_dispatcher()
    {
        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.system.event_dispatcher')
            ->willReturn($expected = $this->getMock(EventDispatcherInterface::class));

        $this->assertSame($expected, $container->getEventDispatcher());
    }

    public function test_returns_logger()
    {
        $container = new SystemContainer($containerMock = TestHelper::getContainerMock($this));
        $containerMock->method('get')
            ->with('lightsaml.system.logger')
            ->willReturn($expected = $this->getMock(LoggerInterface::class));

        $this->assertSame($expected, $container->getLogger());
    }
}
