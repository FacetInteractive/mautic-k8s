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

class UrlTypeTest extends TextTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\UrlType';

    /**
     * @group legacy
     */
    public function testLegacyName()
    {
        $form = $this->factory->create('url');

        $this->assertSame('url', $form->getConfig()->getType()->getName());
    }

    public function testSubmitAddsDefaultProtocolIfNoneIsIncluded()
    {
        $form = $this->factory->create(static::TESTED_TYPE, 'name');

        $form->submit('www.domain.com');

        $this->assertSame('http://www.domain.com', $form->getData());
        $this->assertSame('http://www.domain.com', $form->getViewData());
    }

    public function testSubmitAddsNoDefaultProtocolIfAlreadyIncluded()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'default_protocol' => 'http',
        ));

        $form->submit('ftp://www.domain.com');

        $this->assertSame('ftp://www.domain.com', $form->getData());
        $this->assertSame('ftp://www.domain.com', $form->getViewData());
    }

    public function testSubmitAddsNoDefaultProtocolIfEmpty()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'default_protocol' => 'http',
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
    }

    public function testSubmitAddsNoDefaultProtocolIfNull()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'default_protocol' => 'http',
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
    }

    public function testSubmitAddsNoDefaultProtocolIfSetToNull()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'default_protocol' => null,
        ));

        $form->submit('www.domain.com');

        $this->assertSame('www.domain.com', $form->getData());
        $this->assertSame('www.domain.com', $form->getViewData());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testThrowExceptionIfDefaultProtocolIsInvalid()
    {
        $this->factory->create(static::TESTED_TYPE, null, array(
            'default_protocol' => array(),
        ));
    }
}
