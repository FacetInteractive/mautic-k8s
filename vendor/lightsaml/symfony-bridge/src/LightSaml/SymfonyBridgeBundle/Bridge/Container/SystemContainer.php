<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\Bridge\Container;

use LightSaml\Build\Container\SystemContainerInterface;
use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SystemContainer implements SystemContainerInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var SessionInterface */
    private $session;

    /** @var TimeProviderInterface */
    private $timeProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param RequestStack             $requestStack
     * @param SessionInterface         $session
     * @param TimeProviderInterface    $timeProvider
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        TimeProviderInterface $timeProvider,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->timeProvider = $timeProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return TimeProviderInterface
     */
    public function getTimeProvider()
    {
        return $this->timeProvider;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
