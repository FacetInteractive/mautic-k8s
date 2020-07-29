<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultConfig()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(true), array(array('secret' => 's3cr3t')));

        $this->assertEquals(
            array_merge(array('secret' => 's3cr3t', 'trusted_hosts' => array()), self::getBundleDefaultConfig()),
            $config
        );
    }

    public function testDoNoDuplicateDefaultFormResources()
    {
        $input = array('templating' => array(
            'form' => array('resources' => array('FrameworkBundle:Form')),
            'engines' => array('php'),
        ));

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(true), array($input));

        $this->assertEquals(array('FrameworkBundle:Form'), $config['templating']['form']['resources']);
    }

    /**
     * @dataProvider getTestValidSessionName
     */
    public function testValidSessionName($sessionName)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(
            new Configuration(true),
            array(array('session' => array('name' => $sessionName)))
        );

        $this->assertEquals($sessionName, $config['session']['name']);
    }

    public function getTestValidSessionName()
    {
        return array(
            array(null),
            array('PHPSESSID'),
            array('a&b'),
            array(',_-!@#$%^*(){}:<>/?'),
        );
    }

    /**
     * @dataProvider getTestInvalidSessionName
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidSessionName($sessionName)
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(true),
            array(array('session' => array('name' => $sessionName)))
        );
    }

    public function getTestInvalidSessionName()
    {
        return array(
            array('a.b'),
            array('a['),
            array('a[]'),
            array('a[b]'),
            array('a=b'),
            array('a+b'),
        );
    }

    /**
     * @dataProvider getTestValidTrustedProxiesData
     */
    public function testValidTrustedProxies($trustedProxies, $processedProxies)
    {
        $processor = new Processor();
        $configuration = new Configuration(true);
        $config = $processor->processConfiguration($configuration, array(array(
            'secret' => 's3cr3t',
            'trusted_proxies' => $trustedProxies,
        )));

        $this->assertEquals($processedProxies, $config['trusted_proxies']);
    }

    public function getTestValidTrustedProxiesData()
    {
        return array(
            array(array('127.0.0.1'), array('127.0.0.1')),
            array(array('::1'), array('::1')),
            array(array('127.0.0.1', '::1'), array('127.0.0.1', '::1')),
            array(null, array()),
            array(false, array()),
            array(array(), array()),
            array(array('10.0.0.0/8'), array('10.0.0.0/8')),
            array(array('::ffff:0:0/96'), array('::ffff:0:0/96')),
            array(array('0.0.0.0/0'), array('0.0.0.0/0')),
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidTypeTrustedProxies()
    {
        $processor = new Processor();
        $configuration = new Configuration(true);
        $processor->processConfiguration($configuration, array(
            array(
                'secret' => 's3cr3t',
                'trusted_proxies' => 'Not an IP address',
            ),
        ));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidValueTrustedProxies()
    {
        $processor = new Processor();
        $configuration = new Configuration(true);
        $processor->processConfiguration($configuration, array(
            array(
                'secret' => 's3cr3t',
                'trusted_proxies' => array('Not an IP address'),
            ),
        ));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage You cannot use assets settings under "framework.templating" and "assets" configurations in the same project.
     * @group legacy
     */
    public function testLegacyInvalidValueAssets()
    {
        $processor = new Processor();
        $configuration = new Configuration(true);
        $processor->processConfiguration($configuration, array(
            array(
                'templating' => array(
                    'engines' => null,
                    'assets_base_urls' => '//example.com',
                ),
                'assets' => null,
            ),
        ));
    }

    protected static function getBundleDefaultConfig()
    {
        return array(
            'http_method_override' => true,
            'trusted_proxies' => array(),
            'ide' => null,
            'default_locale' => 'en',
            'form' => array(
                'enabled' => false,
                'csrf_protection' => array(
                    'enabled' => null, // defaults to csrf_protection.enabled
                    'field_name' => null,
                ),
            ),
            'csrf_protection' => array(
                'enabled' => false,
                'field_name' => '_token',
            ),
            'esi' => array('enabled' => false),
            'ssi' => array('enabled' => false),
            'fragments' => array(
                'enabled' => false,
                'path' => '/_fragment',
            ),
            'profiler' => array(
                'enabled' => false,
                'only_exceptions' => false,
                'only_master_requests' => false,
                'dsn' => 'file:%kernel.cache_dir%/profiler',
                'username' => '',
                'password' => '',
                'lifetime' => 86400,
                'collect' => true,
            ),
            'translator' => array(
                'enabled' => false,
                'fallbacks' => array('en'),
                'logging' => true,
                'paths' => array(),
            ),
            'validation' => array(
                'enabled' => false,
                'enable_annotations' => false,
                'static_method' => array('loadValidatorMetadata'),
                'translation_domain' => 'validators',
                'strict_email' => false,
            ),
            'annotations' => array(
                'cache' => 'file',
                'file_cache_dir' => '%kernel.cache_dir%/annotations',
                'debug' => true,
            ),
            'serializer' => array(
                'enabled' => false,
                'enable_annotations' => false,
            ),
            'property_access' => array(
                'magic_call' => false,
                'throw_exception_on_invalid_index' => false,
            ),
            'property_info' => array(
                'enabled' => false,
            ),
            'assets' => array(
                'version' => null,
                'version_format' => '%%s?%%s',
                'base_path' => '',
                'base_urls' => array(),
                'packages' => array(),
            ),
        );
    }
}
