<?php

/*
 * This file is part of the LightSAML Symfony Bridge Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SymfonyBridgeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddMethodCallCompilerPass implements CompilerPassInterface
{
    /** @var string */
    private $serviceId;

    /** @var string */
    private $tagName;

    /** @var string */
    private $methodName;

    /**
     * @param $serviceId
     * @param $tagName
     * @param $methodName
     */
    public function __construct($serviceId, $tagName, $methodName)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->methodName = $methodName;
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->has($this->serviceId)) {
            return;
        }

        $definition = $container->findDefinition($this->serviceId);

        $taggedServices = $container->findTaggedServiceIds($this->tagName);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall($this->methodName, [new Reference($id)]);
        }
    }
}
