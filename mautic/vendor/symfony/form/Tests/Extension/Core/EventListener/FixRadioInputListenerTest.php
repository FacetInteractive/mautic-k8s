<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\ArrayKeyChoiceList;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\EventListener\FixRadioInputListener;

/**
 * @group legacy
 */
class FixRadioInputListenerTest extends TestCase
{
    private $choiceList;

    protected function setUp()
    {
        parent::setUp();

        $this->choiceList = new ArrayKeyChoiceList(array('' => 'Empty', 0 => 'A', 1 => 'B'));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $listener = null;
    }

    public function testFixRadio()
    {
        $data = '1';
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $listener = new FixRadioInputListener($this->choiceList, true);
        $listener->preSubmit($event);

        $this->assertEquals(array(2 => '1'), $event->getData());
    }

    public function testFixZero()
    {
        $data = '0';
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $listener = new FixRadioInputListener($this->choiceList, true);
        $listener->preSubmit($event);

        $this->assertEquals(array(1 => '0'), $event->getData());
    }

    public function testFixEmptyString()
    {
        $data = '';
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $listener = new FixRadioInputListener($this->choiceList, true);
        $listener->preSubmit($event);

        $this->assertEquals(array(0 => ''), $event->getData());
    }

    public function testConvertEmptyStringToPlaceholderIfNotFound()
    {
        $list = new ArrayKeyChoiceList(array(0 => 'A', 1 => 'B'));

        $data = '';
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $listener = new FixRadioInputListener($list, true);
        $listener->preSubmit($event);

        $this->assertEquals(array('placeholder' => ''), $event->getData());
    }

    public function testDontConvertEmptyStringToPlaceholderIfNoPlaceholderUsed()
    {
        $list = new ArrayKeyChoiceList(array(0 => 'A', 1 => 'B'));

        $data = '';
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $listener = new FixRadioInputListener($list, false);
        $listener->preSubmit($event);

        $this->assertEquals(array(), $event->getData());
    }
}
