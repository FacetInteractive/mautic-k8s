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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class LightSamlSymfonyBridgeExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $config    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('container.yml');
        $loader->load('own.yml');
        $loader->load('system.yml');
        $loader->load('party.yml');
        $loader->load('store.yml');
        $loader->load('credential.yml');
        $loader->load('service.yml');
        $loader->load('provider.yml');
        $loader->load('profile.yml');

        $this->configureOwn($container, $config);
        $this->configureSystem($container, $config);
        $this->configureParty($container, $config);
        $this->configureStore($container, $config);
        $this->configureCredential($container, $config);
        $this->configureService($container, $config);
    }

    private function configureCredential(ContainerBuilder $container, array $config)
    {
        $this->configureCredentialStore($container, $config);
    }

    private function configureCredentialStore(ContainerBuilder $container, array $config)
    {
        $factoryReference = new Reference('lightsaml.credential.credential_store_factory');
        $definition = $container->getDefinition('lightsaml.credential.credential_store');
        $this->setFactoryCompatibleWay($definition, $factoryReference, 'buildFromOwnCredentialStore');
    }

    private function configureService(ContainerBuilder $container, array $config)
    {
        $this->configureServiceCredentialResolver($container, $config);
    }

    private function configureServiceCredentialResolver(ContainerBuilder $container, array $config)
    {
        $factoryReference = new Reference('lightsaml.service.credential_resolver_factory');
        $definition = $container->getDefinition('lightsaml.service.credential_resolver');
        $this->setFactoryCompatibleWay($definition, $factoryReference, 'build');
    }

    private function configureOwn(ContainerBuilder $container, array $config)
    {
        $container->setParameter('lightsaml.own.entity_id', $config['own']['entity_id']);

        $this->configureOwnEntityDescriptor($container, $config);
        $this->configureOwnCredentials($container, $config);
    }

    private function configureOwnEntityDescriptor(ContainerBuilder $container, array $config)
    {
        if (isset($config['own']['entity_descriptor_provider']['id'])) {
            $container->setAlias('lightsaml.own.entity_descriptor_provider', $config['own']['entity_descriptor_provider']['id']);
        } elseif (isset($config['own']['entity_descriptor_provider']['filename'])) {
            if (isset($config['own']['entity_descriptor_provider']['entity_id'])) {
                $definition = $container->setDefinition('lightsaml.own.entity_descriptor_provider', new Definition());
                $definition
                    ->addArgument($config['own']['entity_descriptor_provider']['filename'])
                    ->addArgument($config['own']['entity_descriptor_provider']['entity_id']);
                $this->setFactoryCompatibleWay($definition, 'LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', 'fromEntitiesDescriptorFile');
            } else {
                $definition = $container->setDefinition('lightsaml.own.entity_descriptor_provider', new Definition())
                    ->addArgument($config['own']['entity_descriptor_provider']['filename']);
                $this->setFactoryCompatibleWay($definition, 'LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', 'fromEntityDescriptorFile');
            }
        } else {
            $definition = $container->getDefinition('lightsaml.own.entity_descriptor_provider');
            $definition
                ->addArgument('%lightsaml.own.entity_id%')
                ->addArgument(new Reference('router'))
                ->addArgument('%lightsaml.route.login_check%')
                ->addArgument(null)
                ->addArgument(new Reference('lightsaml.own.credential_store'))
            ;
            $this->setFactoryCompatibleWay($definition, 'LightSaml\SymfonyBridgeBundle\Factory\OwnEntityDescriptorProviderFactory', 'build');
        }
    }

    private function configureOwnCredentials(ContainerBuilder $container, array $config)
    {
        if (false === isset($config['own']['credentials'])) {
            return;
        }

        foreach ($config['own']['credentials'] as $id => $data) {
            $definition = new Definition(
                'LightSaml\Store\Credential\X509FileCredentialStore',
                [
                    $config['own']['entity_id'],
                    $data['certificate'],
                    $data['key'],
                    $data['password'],
                ]
            );
            $definition->addTag('lightsaml.own_credential_store');
            $container->setDefinition('lightsaml.own.credential_store.'.$id, $definition);
        }
    }

    private function configureSystem(ContainerBuilder $container, array $config)
    {
        if (isset($config['system']['event_dispatcher'])) {
            $container->removeDefinition('lightsaml.system.event_dispatcher');
            $container->setAlias('lightsaml.system.event_dispatcher', $config['system']['event_dispatcher']);
        }

        if (isset($config['system']['logger'])) {
            $container->setAlias('lightsaml.system.logger', $config['system']['logger']);
        }
    }

    private function configureParty(ContainerBuilder $container, array $config)
    {
        if (isset($config['party']['idp']['files'])) {
            $store = $container->getDefinition('lightsaml.party.idp_entity_descriptor_store');
            foreach ($config['party']['idp']['files'] as $id => $file) {
                $id = sprintf('lightsaml.party.idp_entity_descriptor_store.file.%s', $id);
                $container
                    ->setDefinition($id, new DefinitionDecorator('lightsaml.party.idp_entity_descriptor_store.file'))
                    ->replaceArgument(0, $file);

                $store->addMethodCall('add', [new Reference($id)]);
            }
        }
    }

    private function configureStore(ContainerBuilder $container, array $config)
    {
        if (isset($config['store']['request'])) {
            $container->setAlias('lightsaml.store.request', $config['store']['request']);
        }
        if (isset($config['store']['id_state'])) {
            $container->setAlias('lightsaml.store.id_state', $config['store']['id_state']);
        }
        if (isset($config['store']['sso_state'])) {
            $container->setAlias('lightsaml.store.sso_state', $config['store']['sso_state']);
        }
    }

    /**
     * @param Definition $definition
     * @param string     $classOrReference
     * @param string     $method
     */
    private function setFactoryCompatibleWay(Definition $definition, $classOrReference, $method)
    {
        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory([$classOrReference, $method]);
        } else {
            if ($classOrReference instanceof Reference) {
                $definition->setFactoryService((string) $classOrReference);
            } else {
                $definition->setFactoryClass($classOrReference);
            }
            $definition->setFactoryMethod($method);
        }
    }
}
