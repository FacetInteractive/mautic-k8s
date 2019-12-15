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

use LightSaml\Credential\CredentialInterface;
use LightSaml\Store\Credential\CredentialStoreInterface;
use LightSaml\Store\Credential\Factory\CredentialFactory;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;

class CredentialStoreFactory
{
    /**
     * @param EntityDescriptorStoreInterface $idpEntityDescriptorStore
     * @param EntityDescriptorStoreInterface $spEntityDescriptorStore
     * @param string                         $ownEntityId
     * @param CredentialStoreInterface       $ownCredentialStore
     * @param CredentialInterface[]          $extraCredentials
     *
     * @return \LightSaml\Store\Credential\CompositeCredentialStore
     */
    public static function build(
        EntityDescriptorStoreInterface $idpEntityDescriptorStore,
        EntityDescriptorStoreInterface $spEntityDescriptorStore,
        $ownEntityId,
        CredentialStoreInterface $ownCredentialStore,
        array $extraCredentials = null
    ) {
        $factory = new CredentialFactory();

        return $factory->build(
            $idpEntityDescriptorStore,
            $spEntityDescriptorStore,
            $ownCredentialStore->getByEntityId($ownEntityId),
            $extraCredentials
        );
    }
}
