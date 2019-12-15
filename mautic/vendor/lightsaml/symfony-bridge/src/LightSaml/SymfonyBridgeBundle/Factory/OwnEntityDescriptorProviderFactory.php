<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\Factory;

use LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder;
use LightSaml\Credential\X509Credential;
use LightSaml\Store\Credential\CredentialStoreInterface;
use Symfony\Component\Routing\RouterInterface;

class OwnEntityDescriptorProviderFactory
{
    /**
     * @param string                   $ownEntityId
     * @param RouterInterface          $router
     * @param string                   $acsRouteName
     * @param string                   $ssoRouteName
     * @param CredentialStoreInterface $ownCredentialStore
     *
     * @return SimpleEntityDescriptorBuilder
     */
    public static function build(
        $ownEntityId,
        RouterInterface $router,
        $acsRouteName,
        $ssoRouteName,
        CredentialStoreInterface $ownCredentialStore
    ) {
        /** @var X509Credential[] $arrOwnCredentials */
        $arrOwnCredentials = $ownCredentialStore->getByEntityId($ownEntityId);
        $builder = new SimpleEntityDescriptorBuilder(
            $ownEntityId,
            $acsRouteName ? $router->generate($acsRouteName, [], RouterInterface::ABSOLUTE_URL) : null,
            $ssoRouteName ? $router->generate($ssoRouteName, [], RouterInterface::ABSOLUTE_URL) : null,
            $arrOwnCredentials[0]->getCertificate()
        );

        return $builder;
    }
}
