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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SystemContainer extends AbstractContainer implements SystemContainerInterface
{
    /**
     * @return Request
     */
    public function getRequest()
    {
        if ($this->container->has('request_stack')) {
            return $this->container->get('request_stack')->getCurrentRequest();
        }

        return $this->container->get('request');
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * @return TimeProviderInterface
     */
    public function getTimeProvider()
    {
        return $this->container->get('lightsaml.system.time_provider');
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->container->get('lightsaml.system.event_dispatcher');
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->container->get('lightsaml.system.logger');
    }
}
