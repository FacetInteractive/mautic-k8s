<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\DataMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;

class PropertyPathMapperTest extends TestCase
{
    /**
     * @var PropertyPathMapper
     */
    private $mapper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $propertyAccessor;

    protected function setUp()
    {
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $this->propertyAccessor = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyAccessorInterface')->getMock();
        $this->mapper = new PropertyPathMapper($this->propertyAccessor);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPropertyPath($path)
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->setConstructorArgs(array($path))
            ->setMethods(array('getValue', 'setValue'))
            ->getMock();
    }

    /**
     * @param FormConfigInterface $config
     * @param bool                $synchronized
     * @param bool                $submitted
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getForm(FormConfigInterface $config, $synchronized = true, $submitted = true)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setConstructorArgs(array($config))
            ->setMethods(array('isSynchronized', 'isSubmitted'))
            ->getMock();

        $form->expects($this->any())
            ->method('isSynchronized')
            ->will($this->returnValue($synchronized));

        $form->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue($submitted));

        return $form;
    }

    public function testMapDataToFormsPassesObjectRefIfByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, array($form));

        // Can't use isIdentical() above because mocks always clone their
        // arguments which can't be disabled in PHPUnit 3.6
        $this->assertSame($engine, $form->getData());
    }

    public function testMapDataToFormsPassesObjectCloneIfNotByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, array($form));

        $this->assertNotSame($engine, $form->getData());
        $this->assertEquals($engine, $form->getData());
    }

    public function testMapDataToFormsIgnoresEmptyPropertyPath()
    {
        $car = new \stdClass();

        $config = new FormConfigBuilder(null, '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $form = $this->getForm($config);

        $this->assertNull($form->getPropertyPath());

        $this->mapper->mapDataToForms($car, array($form));

        $this->assertNull($form->getData());
    }

    public function testMapDataToFormsIgnoresUnmapped()
    {
        $car = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setMapped(false);
        $config->setPropertyPath($propertyPath);
        $form = $this->getForm($config);

        $this->mapper->mapDataToForms($car, array($form));

        $this->assertNull($form->getData());
    }

    public function testMapDataToFormsSetsDefaultDataIfPassedDataIsNull()
    {
        $default = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($default);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setConstructorArgs(array($config))
            ->setMethods(array('setData'))
            ->getMock();

        $form->expects($this->once())
            ->method('setData')
            ->with($default);

        $this->mapper->mapDataToForms(null, array($form));
    }

    public function testMapDataToFormsSetsDefaultDataIfPassedDataIsEmptyArray()
    {
        $default = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($default);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setConstructorArgs(array($config))
            ->setMethods(array('setData'))
            ->getMock();

        $form->expects($this->once())
            ->method('setData')
            ->with($default);

        $this->mapper->mapDataToForms(array(), array($form));
    }

    public function testMapFormsToDataWritesBackIfNotByReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceButNoReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($car, $propertyPath, $engine);

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataWritesBackIfByReferenceAndReference()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        // $car already contains the reference of $engine
        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($car, $propertyPath)
            ->will($this->returnValue($engine));

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataIgnoresUnmapped()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setMapped(false);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataIgnoresUnsubmittedForms()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, true, false);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataIgnoresEmptyData()
    {
        $car = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData(null);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataIgnoresUnsynchronized()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = $this->getForm($config, false);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    public function testMapFormsToDataIgnoresDisabled()
    {
        $car = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = $this->getPropertyPath('engine');

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $config = new FormConfigBuilder('name', '\stdClass', $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setDisabled(true);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $car);
    }

    /**
     * @dataProvider provideDate
     */
    public function testMapFormsToDataDoesNotChangeEqualDateTimeInstance($date)
    {
        $article = array();
        $publishedAt = $date;
        $article['publishedAt'] = clone $publishedAt;
        $propertyPath = $this->getPropertyPath('[publishedAt]');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($article['publishedAt'])
        ;
        $this->propertyAccessor->expects($this->never())
            ->method('setValue')
        ;

        $config = new FormConfigBuilder('publishedAt', \get_class($publishedAt), $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($publishedAt);
        $form = $this->getForm($config);

        $this->mapper->mapFormsToData(array($form), $article);
    }

    public function provideDate()
    {
        $data = array(
            '\DateTime' => array(new \DateTime()),
        );

        if (class_exists('DateTimeImmutable')) {
            $data['\DateTimeImmutable'] = array(new \DateTimeImmutable());
        }

        return $data;
    }
}
