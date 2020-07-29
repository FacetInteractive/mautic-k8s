<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 */
class ExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!class_exists('Symfony\Component\Asset\Packages')) {
            $container->removeDefinition('twig.extension.assets');
        }

        if (!class_exists('Symfony\Component\ExpressionLanguage\Expression')) {
            $container->removeDefinition('twig.extension.expression');
        }

        if (!interface_exists('Symfony\Component\Routing\Generator\UrlGeneratorInterface')) {
            $container->removeDefinition('twig.extension.routing');
        }
        if (!interface_exists('Symfony\Component\Translation\TranslatorInterface')) {
            $container->removeDefinition('twig.extension.trans');
        }

        if (!class_exists('Symfony\Component\Yaml\Yaml')) {
            $container->removeDefinition('twig.extension.yaml');
        }

        if ($container->has('form.extension')) {
            $container->getDefinition('twig.extension.form')->addTag('twig.extension');
            $reflClass = new \ReflectionClass('Symfony\Bridge\Twig\Extension\FormExtension');

            $coreThemePath = \dirname(\dirname($reflClass->getFileName())).'/Resources/views/Form';
            $container->getDefinition('twig.loader.native_filesystem')->addMethodCall('addPath', array($coreThemePath));

            $paths = $container->getDefinition('twig.template_iterator')->getArgument(2);
            $paths[$coreThemePath] = null;
            $container->getDefinition('twig.template_iterator')->replaceArgument(2, $paths);

            if ($container->hasDefinition('twig.cache_warmer')) {
                $paths = $container->getDefinition('twig.cache_warmer')->getArgument(2);
                $paths[$coreThemePath] = null;
                $container->getDefinition('twig.cache_warmer')->replaceArgument(2, $paths);
            }
        }

        if ($container->has('fragment.handler')) {
            $container->getDefinition('twig.extension.actions')->addTag('twig.extension');
        }

        if ($container->has('translator')) {
            $container->getDefinition('twig.extension.trans')->addTag('twig.extension');
        }

        if ($container->has('router')) {
            $container->getDefinition('twig.extension.routing')->addTag('twig.extension');
        }

        if ($container->has('fragment.handler')) {
            $container->getDefinition('twig.extension.httpkernel')->addTag('twig.extension');

            // inject Twig in the hinclude service if Twig is the only registered templating engine
            if (
                !$container->hasParameter('templating.engines')
                || array('twig') == $container->getParameter('templating.engines')
            ) {
                $container->getDefinition('fragment.renderer.hinclude')
                    ->addTag('kernel.fragment_renderer', array('alias' => 'hinclude'))
                    ->replaceArgument(0, new Reference('twig'))
                ;
            }
        }

        if ($container->has('request_stack')) {
            $container->getDefinition('twig.extension.httpfoundation')->addTag('twig.extension');
        }

        if ($container->hasParameter('templating.helper.code.file_link_format')) {
            $container->getDefinition('twig.extension.code')->replaceArgument(0, $container->getParameter('templating.helper.code.file_link_format'));
        }

        if ($container->getParameter('kernel.debug')) {
            $container->getDefinition('twig.extension.profiler')->addTag('twig.extension');

            // only register if the improved version from DebugBundle is *not* present
            if (!$container->has('twig.extension.dump')) {
                $container->getDefinition('twig.extension.debug')->addTag('twig.extension');
            }
        }

        $twigLoader = $container->getDefinition('twig.loader.native_filesystem');
        if ($container->has('templating')) {
            $loader = $container->getDefinition('twig.loader.filesystem');
            $loader->setMethodCalls(array_merge($twigLoader->getMethodCalls(), $loader->getMethodCalls()));

            $twigLoader->clearTag('twig.loader');
        } else {
            $container->setAlias('twig.loader.filesystem', new Alias('twig.loader.native_filesystem', false));
            $container->removeDefinition('templating.engine.twig');
        }

        if ($container->has('assets.packages')) {
            $container->getDefinition('twig.extension.assets')->addTag('twig.extension');
        }

        if (method_exists('Symfony\Bridge\Twig\AppVariable', 'setContainer')) {
            // we are on Symfony <3.0, where the setContainer method exists
            $container->getDefinition('twig.app_variable')->addMethodCall('setContainer', array(new Reference('service_container')));
        }
    }
}
