<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle;

use LightSaml\SymfonyBridgeBundle\DependencyInjection\Compiler\AddMethodCallCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LightSamlSymfonyBridgeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddMethodCallCompilerPass(
            'lightsaml.own.credential_store',
            'lightsaml.own_credential_store',
            'add'
        ));
        $container->addCompilerPass(new AddMethodCallCompilerPass(
            'lightsaml.party.trust_options_store',
            'lightsaml.trust_options_store',
            'add'
        ));
        $container->addCompilerPass(new AddMethodCallCompilerPass(
            'lightsaml.party.idp_entity_descriptor_store',
            'lightsaml.idp_entity_store',
            'add'
        ));
        $container->addCompilerPass(new AddMethodCallCompilerPass(
            'lightsaml.credential.credential_store_factory',
            'lightsaml.credential',
            'addExtraCredential'
        ));
    }
}
