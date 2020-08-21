<?php

namespace LightSaml\SymfonyBridgeBundle\Tests\DependencyInjection\Compiler;

use LightSaml\SymfonyBridgeBundle\DependencyInjection\Compiler\AddMethodCallCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AddMethodCallCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_three_strings()
    {
        new AddMethodCallCompilerPass('', '', '');
    }

    public function test_process_does_nothing_if_container_does_not_have_the_service()
    {
        $pass = new AddMethodCallCompilerPass($serviceId = 'service.id', $tagName = 'tag', $methodName = 'method');
        $containerBuilder = new ContainerBuilder(new ParameterBag());

        $pass->process($containerBuilder);

        $this->assertCount(0, $containerBuilder->getDefinitions());
    }

    public function test_process_adds_calls_to_service_with_argument_reference_to_all_tagged_services()
    {
        $pass = new AddMethodCallCompilerPass($serviceId = 'service.id', $tagName = 'tag', $methodName = 'method');
        $containerBuilder = new ContainerBuilder(new ParameterBag());

        $containerBuilder->setDefinition($serviceId, $serviceDefinition = new Definition());
        $containerBuilder->setDefinition($t1 = 't1', (new Definition())->addTag($tagName));
        $containerBuilder->setDefinition('x', new Definition());
        $containerBuilder->setDefinition($t2 = 't2', (new Definition())->addTag($tagName));

        $pass->process($containerBuilder);

        $calls = $serviceDefinition->getMethodCalls();
        $this->assertCount(2, $calls);

        $this->assertEquals($methodName, $calls[0][0]);
        $this->assertEquals($methodName, $calls[1][0]);

        $this->assertEquals($t1, (string) $calls[0][1][0]);
        $this->assertEquals($t2, (string) $calls[1][1][0]);
    }
}
