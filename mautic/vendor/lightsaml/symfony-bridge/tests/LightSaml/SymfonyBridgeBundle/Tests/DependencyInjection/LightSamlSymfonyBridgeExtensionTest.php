<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\DependencyInjection;

use LightSaml\SymfonyBridgeBundle\DependencyInjection\LightSamlSymfonyBridgeExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LightSamlSymfonyBridgeExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function test_loads_with_configuration()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();

        $extension->load($config, $containerBuilder);
    }

    public function test_loads_build_container()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.container.build'));
        $this->assertEquals('LightSaml\SymfonyBridgeBundle\Bridge\Container\BuildContainer', $containerBuilder->getDefinition('lightsaml.container.build')->getClass());
    }

    public function test_set_entity_id_parameter_from_configuration()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $extension->load($config, $containerBuilder);

        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_id'], $containerBuilder->getParameter('lightsaml.own.entity_id'));
    }

    public function test_sets_own_entity_descriptor_provider_factory_from_entity_descriptor_file()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['filename'] = 'file.xml';
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.own.entity_descriptor_provider'));
        $definition = $containerBuilder->getDefinition('lightsaml.own.entity_descriptor_provider');
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(
                ['LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', 'fromEntityDescriptorFile'],
                $definition->getFactory()
            );
        } else {
            $this->assertEquals('LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', $definition->getFactoryClass());
            $this->assertEquals('fromEntityDescriptorFile', $definition->getFactoryMethod());
        }
        $this->assertCount(1, $definition->getArguments());
        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['filename'], $definition->getArgument(0));
    }

    public function test_sets_swn_entity_descriptor_provider_factory_from_entities_descriptor_file_and_entity_id()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['filename'] = 'file.xml';
        $config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['entity_id'] = 'some-id';
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.own.entity_descriptor_provider'));
        $definition = $containerBuilder->getDefinition('lightsaml.own.entity_descriptor_provider');
        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(
                ['LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', 'fromEntitiesDescriptorFile'],
                $definition->getFactory()
            );
        } else {
            $this->assertEquals('LightSaml\Provider\EntityDescriptor\FileEntityDescriptorProviderFactory', $definition->getFactoryClass());
            $this->assertEquals('fromEntitiesDescriptorFile', $definition->getFactoryMethod());
        }
        $this->assertCount(2, $definition->getArguments());
        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['filename'], $definition->getArgument(0));
        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['entity_id'], $definition->getArgument(1));
    }

    public function test_sets_own_entity_descriptor_provider_to_custom_alias()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['own']['entity_descriptor_provider']['id'] = $expectedAlias = 'some.factory';

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasAlias('lightsaml.own.entity_descriptor_provider'));
        $this->assertEquals($expectedAlias, (string) $containerBuilder->getAlias('lightsaml.own.entity_descriptor_provider'));
    }

    public function test_adds_own_file_credentials()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['own']['credentials'] = [
            [
                'certificate' => $firstCertificate = 'first.crt',
                'key' => $firstKey = 'first.key',
                'password' => $firstPassword = 'pw1',
            ],
            [
                'certificate' => $secondCertificate = 'second.crt',
                'key' => $secondKey = 'second.key',
                'password' => $secondPassword = null,
            ],
        ];

        $extension->load($config, $containerBuilder);

        $taggedServices = $containerBuilder->findTaggedServiceIds('lightsaml.own_credential_store');

        $this->assertCount(2, $taggedServices);

        $this->assertTrue($containerBuilder->has('lightsaml.own.credential_store.0'));
        $definition = $containerBuilder->getDefinition('lightsaml.own.credential_store.0');
        $this->assertEquals(\LightSaml\Store\Credential\X509FileCredentialStore::class, $definition->getClass());
        $this->assertCount(4, $definition->getArguments());
        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_id'], $definition->getArgument(0));
        $this->assertEquals($firstCertificate, $definition->getArgument(1));
        $this->assertEquals($firstKey, $definition->getArgument(2));
        $this->assertEquals($firstPassword, $definition->getArgument(3));

        $this->assertTrue($containerBuilder->has('lightsaml.own.credential_store.1'));
        $definition = $containerBuilder->getDefinition('lightsaml.own.credential_store.1');
        $this->assertEquals(\LightSaml\Store\Credential\X509FileCredentialStore::class, $definition->getClass());
        $this->assertCount(4, $definition->getArguments());
        $this->assertEquals($config['light_saml_symfony_bridge']['own']['entity_id'], $definition->getArgument(0));
        $this->assertEquals($secondCertificate, $definition->getArgument(1));
        $this->assertEquals($secondKey, $definition->getArgument(2));
        $this->assertEquals($secondPassword, $definition->getArgument(3));
    }

    public function test_adds_idp_entities_from_file()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['party']['idp']['files'] = [
            $idp1 = 'first.xml',
            $idp2 = 'second.xml',
        ];

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->has('lightsaml.party.idp_entity_descriptor_store.file.0'));
        $this->assertTrue($containerBuilder->has('lightsaml.party.idp_entity_descriptor_store.file.1'));

        $this->assertEquals($idp1, $containerBuilder->getDefinition('lightsaml.party.idp_entity_descriptor_store.file.0')->getArgument(0));
        $this->assertEquals($idp2, $containerBuilder->getDefinition('lightsaml.party.idp_entity_descriptor_store.file.1')->getArgument(0));

        $storeDefinition = $containerBuilder->getDefinition('lightsaml.party.idp_entity_descriptor_store');
        $calls = $storeDefinition->getMethodCalls();

        $this->assertCount(2, $calls);

        $this->assertEquals('add', $calls[0][0]);
        $this->assertEquals('lightsaml.party.idp_entity_descriptor_store.file.0', (string) $calls[0][1][0]);

        $this->assertEquals('add', $calls[1][0]);
        $this->assertEquals('lightsaml.party.idp_entity_descriptor_store.file.1', (string) $calls[1][1][0]);
    }

    public function test_sets_store_request_alias()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['store']['request'] = $expected = 'service.id';

        $extension->load($config, $containerBuilder);

        $this->assertEquals($expected, (string) $containerBuilder->getAlias('lightsaml.store.request'));
    }

    public function test_sets_store_id_state_alias()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['store']['id_state'] = $expected = 'service.id';

        $extension->load($config, $containerBuilder);

        $this->assertEquals($expected, (string) $containerBuilder->getAlias('lightsaml.store.id_state'));
    }

    public function test_sets_store_sso_state_alias()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['store']['sso_state'] = $expected = 'service.id';

        $extension->load($config, $containerBuilder);

        $this->assertEquals($expected, (string) $containerBuilder->getAlias('lightsaml.store.sso_state'));
    }

    public function test_loads_own_credential_store()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.own.credential_store'));
        $definition = $containerBuilder->getDefinition('lightsaml.own.credential_store');
        $this->assertEquals('LightSaml\Store\Credential\CompositeCredentialStore', $definition->getClass());
    }

    public function testLoadsSystemTimeProvider()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.system.time_provider'));
    }

    public function test_loads_system_event_dispatcher()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('lightsaml.system.event_dispatcher'));
    }

    public function test_loads_system_custom_event_dispatcher()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['system']['event_dispatcher'] = $expectedAlias = 'some.service';

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasAlias('lightsaml.system.event_dispatcher'));
        $this->assertEquals($expectedAlias, (string) $containerBuilder->getAlias('lightsaml.system.event_dispatcher'));
    }

    public function test_loads_system_logger()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasAlias('lightsaml.system.logger'));
    }

    public function test_loads_system_custom_logger()
    {
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension = new LightSamlSymfonyBridgeExtension();
        $config = $this->getDefaultConfig();
        $config['light_saml_symfony_bridge']['system']['logger'] = $expectedAlias = 'some.service';

        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasAlias('lightsaml.system.logger'));
        $this->assertEquals($expectedAlias, (string) $containerBuilder->getAlias('lightsaml.system.logger'));
    }

    private function getDefaultConfig()
    {
        return [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://localhost/symfony-bridge',
                ],
            ],
        ];
    }
}
