<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\NativeRequestHandler;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileTypeTest extends BaseTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\FileType';

    /**
     * @group legacy
     */
    public function testLegacyName()
    {
        $form = $this->factory->create('file');

        $this->assertSame('file', $form->getConfig()->getType()->getName());
    }

    // https://github.com/symfony/symfony/pull/5028
    public function testSetData()
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE)->getForm();
        $data = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')
            ->setConstructorArgs(array(__DIR__.'/../../../Fixtures/foo', 'foo'))
            ->getMock();

        $form->setData($data);

        // Ensures the data class is defined to accept File instance
        $this->assertSame($data, $form->getData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testSubmit(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE)->setRequestHandler($requestHandler)->getForm();
        $data = $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo', 'foo.jpg');

        $form->submit($data);

        $this->assertSame($data, $form->getData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testSetDataMultiple(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE, null, array(
            'multiple' => true,
        ))->setRequestHandler($requestHandler)->getForm();

        $data = array(
            $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo', 'foo.jpg'),
            $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo2', 'foo2.jpg'),
        );

        $form->setData($data);
        $this->assertSame($data, $form->getData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testSubmitMultiple(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE, null, array(
            'multiple' => true,
        ))->setRequestHandler($requestHandler)->getForm();

        $data = array(
            $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo', 'foo.jpg'),
            $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo2', 'foo2.jpg'),
        );

        $form->submit($data);
        $this->assertSame($data, $form->getData());

        $view = $form->createView();
        $this->assertSame('file[]', $view->vars['full_name']);
        $this->assertArrayHasKey('multiple', $view->vars['attr']);
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testDontPassValueToView(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE)->setRequestHandler($requestHandler)->getForm();
        $form->submit(array(
            'file' => $this->createUploadedFileMock($requestHandler, __DIR__.'/../../../Fixtures/foo', 'foo.jpg'),
        ));

        $this->assertEquals('', $form->createView()->vars['value']);
    }

    public function testPassMultipartFalseToView()
    {
        $view = $this->factory->create(static::TESTED_TYPE)
            ->createView();

        $this->assertTrue($view->vars['multipart']);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        parent::testSubmitNull($expected, $norm, '');
    }

    public function testSubmitNullWhenMultiple()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'multiple' => true,
        ));
        // submitted data when an input file is uploaded without choosing any file
        $form->submit(array(null));

        $this->assertSame(array(), $form->getData());
        $this->assertSame(array(), $form->getNormData());
        $this->assertSame(array(), $form->getViewData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testSubmittedFilePathsAreDropped(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory->createBuilder(static::TESTED_TYPE)->setRequestHandler($requestHandler)->getForm();
        $form->submit('file:///etc/passwd');

        $this->assertNull($form->getData());
        $this->assertNull($form->getNormData());
        $this->assertSame('', $form->getViewData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testMultipleSubmittedFilePathsAreDropped(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory
            ->createBuilder(static::TESTED_TYPE, null, array(
                'multiple' => true,
            ))
            ->setRequestHandler($requestHandler)
            ->getForm();
        $form->submit(array(
            'file:///etc/passwd',
            $this->createUploadedFileMock(new HttpFoundationRequestHandler(), __DIR__.'/../../../Fixtures/foo', 'foo.jpg'),
            $this->createUploadedFileMock(new NativeRequestHandler(), __DIR__.'/../../../Fixtures/foo2', 'foo2.jpg'),
        ));

        $this->assertCount(1, $form->getData());
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testSubmitNonArrayValueWhenMultiple(RequestHandlerInterface $requestHandler)
    {
        $form = $this->factory
            ->createBuilder(static::TESTED_TYPE, null, array(
                'multiple' => true,
            ))
            ->setRequestHandler($requestHandler)
            ->getForm();
        $form->submit(null);

        $this->assertSame(array(), $form->getData());
        $this->assertSame(array(), $form->getNormData());
        $this->assertSame(array(), $form->getViewData());
    }

    public function requestHandlerProvider()
    {
        return array(
            array(new HttpFoundationRequestHandler()),
            array(new NativeRequestHandler()),
        );
    }

    private function createUploadedFileMock(RequestHandlerInterface $requestHandler, $path, $originalName)
    {
        if ($requestHandler instanceof HttpFoundationRequestHandler) {
            return new UploadedFile($path, $originalName, null, 10, null, true);
        }

        return array(
            'name' => $originalName,
            'error' => 0,
            'type' => 'text/plain',
            'tmp_name' => $path,
            'size' => 10,
        );
    }
}
