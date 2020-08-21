<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\DependencyInjection;

use LightSaml\SymfonyBridgeBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function test_passes_with_own_entity_id_only()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "own" at path "light_saml_symfony_bridge" must be configured
     */
    public function test_does_not_allow_empty_config()
    {
        $config = [
            'light_saml_symfony_bridge' => [

            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_own_entity_descriptor_provider_from_file()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                    'entity_descriptor_provider' => [
                        'filename' => '/some/path',
                    ],
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_own_entity_descriptor_provider_from_file_with_entity_id()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                    'entity_descriptor_provider' => [
                        'filename' => '/some/path',
                        'entity_id' => 'id',
                    ],
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_own_entity_descriptor_provider_from_service()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                    'entity_descriptor_provider' => [
                        'id' => 'some.service',
                    ],
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_own_credentials_from_files()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                    'credentials' => [
                        [
                            'certificate' => '/some/path.crt',
                            'key' => '/some/path.pem',
                            'password' => 'aaa',
                        ],
                        [
                            'certificate' => '/other/path.crt',
                            'key' => '/other/path.pem',
                        ],
                    ],
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_system_event_dispatcher()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'system' => [
                    'event_dispatcher' => 'some.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_system_logger()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'system' => [
                    'logger' => 'some.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_store_request()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'store' => [
                    'request' => 'some.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_store_id_state()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'store' => [
                    'id_state' => 'some.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_store_sso_state()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'store' => [
                    'sso_state' => 'some.id',
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    public function test_allows_party_idp_from_files()
    {
        $config = [
            'light_saml_symfony_bridge' => [
                'own' => [
                    'entity_id' => 'http://own.id',
                ],
                'party' => [
                    'idp' => [
                        'files' => [
                            'first.xml',
                            'second.xml',
                        ],
                    ],
                ],
            ],
        ];
        $this->processConfiguration($config);
    }

    /**
     * @param array $configs
     *
     * @return array
     */
    protected function processConfiguration(array $configs)
    {
        $configuration = new Configuration();
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }
}
