<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('light_saml_symfony_bridge');

        $root->children()
            ->arrayNode('own')
                ->isRequired()
                ->children()
                    ->scalarNode('entity_id')->isRequired()->cannotBeEmpty()->end()
                    ->arrayNode('entity_descriptor_provider')
                        ->children()
                            ->scalarNode('id')->end()
                            ->scalarNode('filename')->end()
                            ->scalarNode('entity_id')->end()
                        ->end()
                    ->end()
                    ->arrayNode('credentials')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('certificate')->end()
                                ->scalarNode('key')->end()
                                ->scalarNode('password')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('system')
                ->children()
                    ->scalarNode('event_dispatcher')->defaultValue(null)->end()
                    ->scalarNode('logger')->defaultValue(null)->end()
                ->end()
            ->end()
            ->arrayNode('store')
                ->children()
                    ->scalarNode('request')->end()
                    ->scalarNode('id_state')->end()
                    ->scalarNode('sso_state')->end()
                ->end()
            ->end()
            ->arrayNode('party')
                ->children()
                    ->arrayNode('idp')
                        ->children()
                            ->arrayNode('files')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
