<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Templating\Asset;

@trigger_error('The Symfony\Component\Templating\Asset\Package is deprecated since Symfony 2.7 and will be removed in 3.0. Use the Asset component instead.', E_USER_DEPRECATED);

/**
 * The basic package will add a version to asset URLs.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 *
 * @deprecated since 2.7, will be removed in 3.0. Use the Asset component instead.
 */
class Package implements PackageInterface
{
    private $version;
    private $format;

    /**
     * @param string $version The package version
     * @param string $format  The format used to apply the version
     */
    public function __construct($version = null, $format = '')
    {
        $this->version = $version;
        $this->format = $format ?: '%s?%s';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($path, $version = null)
    {
        if (false !== strpos($path, '://') || 0 === strpos($path, '//')) {
            return $path;
        }

        return $this->applyVersion($path, $version);
    }

    /**
     * Applies version to the supplied path.
     *
     * @param string           $path    A path
     * @param string|bool|null $version A specific version
     *
     * @return string The versionized path
     */
    protected function applyVersion($path, $version = null)
    {
        $version = null !== $version ? $version : $this->version;
        if (null === $version || false === $version) {
            return $path;
        }

        $versionized = sprintf($this->format, ltrim($path, '/'), $version);

        if ($path && '/' == $path[0]) {
            $versionized = '/'.$versionized;
        }

        return $versionized;
    }
}
