<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Model\Bundle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generates a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BundleGenerator extends Generator
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generateBundle(Bundle $bundle)
    {
        $dir = $bundle->getTargetDirectory();

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $parameters = array(
            'namespace' => $bundle->getNamespace(),
            'bundle' => $bundle->getName(),
            'format' => $bundle->getConfigurationFormat(),
            'bundle_basename' => $bundle->getBasename(),
            'extension_alias' => $bundle->getExtensionAlias(),
        );

        $this->renderFile('bundle/Bundle.php.twig', $dir.'/'.$bundle->getName().'.php', $parameters);
        if ($bundle->shouldGenerateDependencyInjectionDirectory()) {
            $this->renderFile('bundle/Extension.php.twig', $dir.'/DependencyInjection/'.$bundle->getBasename().'Extension.php', $parameters);
            $this->renderFile('bundle/Configuration.php.twig', $dir.'/DependencyInjection/Configuration.php', $parameters);
        }
        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Controller/DefaultController.php', $parameters);
        $this->renderFile('bundle/DefaultControllerTest.php.twig', $bundle->getTestsDirectory().'/Controller/DefaultControllerTest.php', $parameters);
        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);

        // render the services.yml/xml file
        $servicesFilename = $bundle->getServicesConfigurationFilename();
        $this->renderFile(
            sprintf('bundle/%s.twig', $servicesFilename),
            $dir.'/Resources/config/'.$servicesFilename, $parameters
        );

        if ($routingFilename = $bundle->getRoutingConfigurationFilename()) {
            $this->renderFile(
                sprintf('bundle/%s.twig', $routingFilename),
                $dir.'/Resources/config/'.$routingFilename, $parameters
            );
        }
    }
}
