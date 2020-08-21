<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Tests\Fixtures\FooType;

class AbstractExtensionTest extends TestCase
{
    public function testHasType()
    {
        $loader = new ConcreteExtension();
        $this->assertTrue($loader->hasType('Symfony\Component\Form\Tests\Fixtures\FooType'));
        $this->assertFalse($loader->hasType('foo'));
    }

    public function testGetType()
    {
        $loader = new ConcreteExtension();
        $this->assertInstanceOf('Symfony\Component\Form\Tests\Fixtures\FooType', $loader->getType('Symfony\Component\Form\Tests\Fixtures\FooType'));
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Custom resolver "Symfony\Component\Form\Tests\Fixtures\CustomOptionsResolver" must extend "Symfony\Component\OptionsResolver\OptionsResolver".
     */
    public function testCustomOptionsResolver()
    {
        $extension = new Fixtures\LegacyFooTypeBarExtension();
        $resolver = new Fixtures\CustomOptionsResolver();
        $extension->setDefaultOptions($resolver);
    }
}

class ConcreteExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return array(new FooType());
    }

    protected function loadTypeGuesser()
    {
    }
}
