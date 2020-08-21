<?php

namespace LightSaml\SymfonyBridgeBundle\Tests;

use LightSaml\SymfonyBridgeBundle\DependencyInjection\Compiler\AddMethodCallCompilerPass;
use LightSaml\SymfonyBridgeBundle\LightSamlSymfonyBridgeBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LightSamlSymfonyBridgeBundleTest extends \PHPUnit_Framework_TestCase
{
    public function build_adds_own_credential_store_compiler_pass_provider()
    {
        return [
            ['lightsaml.own.credential_store', 'lightsaml.own_credential_store', 'add'],
            ['lightsaml.party.trust_options_store', 'lightsaml.trust_options_store', 'add'],
            ['lightsaml.party.idp_entity_descriptor_store', 'lightsaml.idp_entity_store', 'add'],
            ['lightsaml.credential.credential_store_factory', 'lightsaml.credential', 'addExtraCredential'],
        ];
    }

    /**
     * @dataProvider  build_adds_own_credential_store_compiler_pass_provider
     *
     * @param $serviceId
     * @param $tagName
     * @param $methodName
     */
    public function test_build_adds_own_credential_store_compiler_pass($serviceId, $tagName, $methodName)
    {
        $bundle = new LightSamlSymfonyBridgeBundle();

        $containerBuilder = new ContainerBuilder(new ParameterBag());

        $bundle->build($containerBuilder);

        $passes = $containerBuilder->getCompilerPassConfig()->getPasses();

        foreach ($passes as $pass) {
            if ($pass instanceof AddMethodCallCompilerPass) {
                if ($pass->getServiceId() == $serviceId &&
                    $pass->getTagName() == $tagName &&
                    $pass->getMethodName() == $methodName
                ) {
                    return;
                }
            }
        }

        $this->fail(sprintf('AddMethodCallCompilerPass with arguments "%s", "%s", "%s" not found', $serviceId, $tagName, $methodName));
    }
}
