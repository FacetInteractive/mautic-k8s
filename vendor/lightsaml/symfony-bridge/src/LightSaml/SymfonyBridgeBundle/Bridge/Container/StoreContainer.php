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

use LightSaml\Build\Container\StoreContainerInterface;
use LightSaml\Store\Id\IdStoreInterface;
use LightSaml\Store\Request\RequestStateStoreInterface;
use LightSaml\Store\Sso\SsoStateStoreInterface;

class StoreContainer implements StoreContainerInterface
{
    /** @var RequestStateStoreInterface */
    private $requestStateStore;

    /** @var IdStoreInterface */
    private $idStateStore;

    /** @var SsoStateStoreInterface */
    private $ssoStateStore;

    /**
     * @param RequestStateStoreInterface $requestStateStore
     * @param IdStoreInterface           $idStateStore
     * @param SsoStateStoreInterface     $ssoStateStore
     */
    public function __construct(
        RequestStateStoreInterface $requestStateStore,
        IdStoreInterface $idStateStore,
        SsoStateStoreInterface $ssoStateStore
    ) {
        $this->requestStateStore = $requestStateStore;
        $this->idStateStore = $idStateStore;
        $this->ssoStateStore = $ssoStateStore;
    }

    /**
     * @return RequestStateStoreInterface
     */
    public function getRequestStateStore()
    {
        return $this->requestStateStore;
    }

    /**
     * @return IdStoreInterface
     */
    public function getIdStateStore()
    {
        return $this->idStateStore;
    }

    /**
     * @return SsoStateStoreInterface
     */
    public function getSsoStateStore()
    {
        return $this->ssoStateStore;
    }
}
