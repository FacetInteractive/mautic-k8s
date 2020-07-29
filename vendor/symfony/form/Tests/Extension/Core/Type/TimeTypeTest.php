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

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormError;

class TimeTypeTest extends BaseTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\TimeType';

    /**
     * @group legacy
     */
    public function testLegacyName()
    {
        $form = $this->factory->create('time');

        $this->assertSame('time', $form->getConfig()->getType()->getName());
    }

    public function testSubmitDateTime()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'datetime',
        ));

        $input = array(
            'hour' => '3',
            'minute' => '4',
        );

        $form->submit($input);

        $dateTime = new \DateTime('1970-01-01 03:04:00 UTC');

        $this->assertEquals($dateTime, $form->getData());
        $this->assertEquals($input, $form->getViewData());
    }

    public function testSubmitString()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'string',
        ));

        $input = array(
            'hour' => '3',
            'minute' => '4',
        );

        $form->submit($input);

        $this->assertEquals('03:04:00', $form->getData());
        $this->assertEquals($input, $form->getViewData());
    }

    public function testSubmitTimestamp()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'timestamp',
        ));

        $input = array(
            'hour' => '3',
            'minute' => '4',
        );

        $form->submit($input);

        $dateTime = new \DateTime('1970-01-01 03:04:00 UTC');

        $this->assertEquals($dateTime->format('U'), $form->getData());
        $this->assertEquals($input, $form->getViewData());
    }

    public function testSubmitArray()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'array',
        ));

        $input = array(
            'hour' => '3',
            'minute' => '4',
        );

        $form->submit($input);

        $this->assertEquals($input, $form->getData());
        $this->assertEquals($input, $form->getViewData());
    }

    public function testSubmitDatetimeSingleText()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'datetime',
            'widget' => 'single_text',
        ));

        $form->submit('03:04');

        $this->assertEquals(new \DateTime('1970-01-01 03:04:00 UTC'), $form->getData());
        $this->assertEquals('03:04', $form->getViewData());
    }

    public function testSubmitDatetimeSingleTextWithoutMinutes()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'datetime',
            'widget' => 'single_text',
            'with_minutes' => false,
        ));

        $form->submit('03');

        $this->assertEquals(new \DateTime('1970-01-01 03:00:00 UTC'), $form->getData());
        $this->assertEquals('03', $form->getViewData());
    }

    public function testSubmitArraySingleText()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'array',
            'widget' => 'single_text',
        ));

        $data = array(
            'hour' => '3',
            'minute' => '4',
        );

        $form->submit('03:04');

        $this->assertEquals($data, $form->getData());
        $this->assertEquals('03:04', $form->getViewData());
    }

    public function testSubmitArraySingleTextWithoutMinutes()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'array',
            'widget' => 'single_text',
            'with_minutes' => false,
        ));

        $data = array(
            'hour' => '3',
        );

        $form->submit('03');

        $this->assertEquals($data, $form->getData());
        $this->assertEquals('03', $form->getViewData());
    }

    public function testSubmitArraySingleTextWithSeconds()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'array',
            'widget' => 'single_text',
            'with_seconds' => true,
        ));

        $data = array(
            'hour' => '3',
            'minute' => '4',
            'second' => '5',
        );

        $form->submit('03:04:05');

        $this->assertEquals($data, $form->getData());
        $this->assertEquals('03:04:05', $form->getViewData());
    }

    public function testSubmitStringSingleText()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'string',
            'widget' => 'single_text',
        ));

        $form->submit('03:04');

        $this->assertEquals('03:04:00', $form->getData());
        $this->assertEquals('03:04', $form->getViewData());
    }

    public function testSubmitStringSingleTextWithoutMinutes()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'string',
            'widget' => 'single_text',
            'with_minutes' => false,
        ));

        $form->submit('03');

        $this->assertEquals('03:00:00', $form->getData());
        $this->assertEquals('03', $form->getViewData());
    }

    public function testSubmitWithSecondsAndBrowserOmissionSeconds()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'string',
            'widget' => 'single_text',
            'with_seconds' => true,
        ));

        $form->submit('03:04');

        $this->assertEquals('03:04:00', $form->getData());
        $this->assertEquals('03:04:00', $form->getViewData());
    }

    public function testSetDataWithoutMinutes()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'datetime',
            'with_minutes' => false,
        ));

        $form->setData(new \DateTime('03:04:05 UTC'));

        $this->assertEquals(array('hour' => 3), $form->getViewData());
    }

    public function testSetDataWithSeconds()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'input' => 'datetime',
            'with_seconds' => true,
        ));

        $form->setData(new \DateTime('03:04:05 UTC'));

        $this->assertEquals(array('hour' => 3, 'minute' => 4, 'second' => 5), $form->getViewData());
    }

    public function testSetDataDifferentTimezones()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Asia/Hong_Kong',
            'input' => 'string',
            'with_seconds' => true,
        ));

        $dateTime = new \DateTime('2013-01-01 12:04:05');
        $dateTime->setTimezone(new \DateTimeZone('America/New_York'));

        $form->setData($dateTime->format('H:i:s'));

        $outputTime = clone $dateTime;
        $outputTime->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        $displayedData = array(
            'hour' => (int) $outputTime->format('H'),
            'minute' => (int) $outputTime->format('i'),
            'second' => (int) $outputTime->format('s'),
        );

        $this->assertEquals($displayedData, $form->getViewData());
    }

    public function testSetDataDifferentTimezonesDateTime()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Asia/Hong_Kong',
            'input' => 'datetime',
            'with_seconds' => true,
        ));

        $dateTime = new \DateTime('12:04:05');
        $dateTime->setTimezone(new \DateTimeZone('America/New_York'));

        $form->setData($dateTime);

        $outputTime = clone $dateTime;
        $outputTime->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        $displayedData = array(
            'hour' => (int) $outputTime->format('H'),
            'minute' => (int) $outputTime->format('i'),
            'second' => (int) $outputTime->format('s'),
        );

        $this->assertEquals($dateTime, $form->getData());
        $this->assertEquals($displayedData, $form->getViewData());
    }

    public function testHoursOption()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'hours' => array(6, 7),
        ));

        $view = $form->createView();

        $this->assertEquals(array(
            new ChoiceView('6', '6', '06'),
            new ChoiceView('7', '7', '07'),
        ), $view['hour']->vars['choices']);
    }

    public function testIsMinuteWithinRangeReturnsTrueIfWithin()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'minutes' => array(6, 7),
        ));

        $view = $form->createView();

        $this->assertEquals(array(
            new ChoiceView('6', '6', '06'),
            new ChoiceView('7', '7', '07'),
        ), $view['minute']->vars['choices']);
    }

    public function testIsSecondWithinRangeReturnsTrueIfWithin()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'seconds' => array(6, 7),
            'with_seconds' => true,
        ));

        $view = $form->createView();

        $this->assertEquals(array(
            new ChoiceView('6', '6', '06'),
            new ChoiceView('7', '7', '07'),
        ), $view['second']->vars['choices']);
    }

    public function testIsPartiallyFilledReturnsFalseIfCompletelyEmpty()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
        ));

        $form->submit(array(
            'hour' => '',
            'minute' => '',
        ));

        $this->assertFalse($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsFalseIfCompletelyEmptyWithSeconds()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
            'with_seconds' => true,
        ));

        $form->submit(array(
            'hour' => '',
            'minute' => '',
            'second' => '',
        ));

        $this->assertFalse($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsFalseIfCompletelyFilled()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
        ));

        $form->submit(array(
            'hour' => '0',
            'minute' => '0',
        ));

        $this->assertFalse($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsFalseIfCompletelyFilledWithSeconds()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
            'with_seconds' => true,
        ));

        $form->submit(array(
            'hour' => '0',
            'minute' => '0',
            'second' => '0',
        ));

        $this->assertFalse($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsTrueIfChoiceAndHourEmpty()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
            'with_seconds' => true,
        ));

        $form->submit(array(
            'hour' => '',
            'minute' => '0',
            'second' => '0',
        ));

        $this->assertTrue($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsTrueIfChoiceAndMinuteEmpty()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
            'with_seconds' => true,
        ));

        $form->submit(array(
            'hour' => '0',
            'minute' => '',
            'second' => '0',
        ));

        $this->assertTrue($form->isPartiallyFilled());
    }

    public function testIsPartiallyFilledReturnsTrueIfChoiceAndSecondsEmpty()
    {
        $this->markTestIncomplete('Needs to be reimplemented using validators');

        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'choice',
            'with_seconds' => true,
        ));

        $form->submit(array(
            'hour' => '0',
            'minute' => '0',
            'second' => '',
        ));

        $this->assertTrue($form->isPartiallyFilled());
    }

    public function testInitializeWithDateTime()
    {
        // Throws an exception if "data_class" option is not explicitly set
        // to null in the type
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $this->factory->create(static::TESTED_TYPE, new \DateTime()));
    }

    public function testSingleTextWidgetShouldUseTheRightInputType()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'single_text',
        ));

        $view = $form->createView();
        $this->assertEquals('time', $view->vars['type']);
    }

    public function testSingleTextWidgetWithSecondsShouldHaveRightStepAttribute()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'single_text',
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertArrayHasKey('step', $view->vars['attr']);
        $this->assertEquals(1, $view->vars['attr']['step']);
    }

    public function testSingleTextWidgetWithSecondsShouldNotOverrideStepAttribute()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'single_text',
            'with_seconds' => true,
            'attr' => array(
                'step' => 30,
            ),
        ));

        $view = $form->createView();
        $this->assertArrayHasKey('step', $view->vars['attr']);
        $this->assertEquals(30, $view->vars['attr']['step']);
    }

    public function testDontPassHtml5TypeIfHtml5NotAllowed()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => 'single_text',
            'html5' => false,
        ));

        $view = $form->createView();
        $this->assertArrayNotHasKey('type', $view->vars);
    }

    public function testPassDefaultPlaceholderToViewIfNotRequired()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'required' => false,
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('', $view['hour']->vars['placeholder']);
        $this->assertSame('', $view['minute']->vars['placeholder']);
        $this->assertSame('', $view['second']->vars['placeholder']);
    }

    public function testPassNoPlaceholderToViewIfRequired()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'required' => true,
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertNull($view['hour']->vars['placeholder']);
        $this->assertNull($view['minute']->vars['placeholder']);
        $this->assertNull($view['second']->vars['placeholder']);
    }

    public function testPassPlaceholderAsString()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'placeholder' => 'Empty',
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('Empty', $view['hour']->vars['placeholder']);
        $this->assertSame('Empty', $view['minute']->vars['placeholder']);
        $this->assertSame('Empty', $view['second']->vars['placeholder']);
    }

    /**
     * @group legacy
     */
    public function testPassEmptyValueBC()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'empty_value' => 'Empty',
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('Empty', $view['hour']->vars['placeholder']);
        $this->assertSame('Empty', $view['minute']->vars['placeholder']);
        $this->assertSame('Empty', $view['second']->vars['placeholder']);
        $this->assertSame('Empty', $view['hour']->vars['empty_value']);
        $this->assertSame('Empty', $view['minute']->vars['empty_value']);
        $this->assertSame('Empty', $view['second']->vars['empty_value']);
    }

    public function testPassPlaceholderAsArray()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'placeholder' => array(
                'hour' => 'Empty hour',
                'minute' => 'Empty minute',
                'second' => 'Empty second',
            ),
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('Empty hour', $view['hour']->vars['placeholder']);
        $this->assertSame('Empty minute', $view['minute']->vars['placeholder']);
        $this->assertSame('Empty second', $view['second']->vars['placeholder']);
    }

    public function testPassPlaceholderAsPartialArrayAddEmptyIfNotRequired()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'required' => false,
            'placeholder' => array(
                'hour' => 'Empty hour',
                'second' => 'Empty second',
            ),
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('Empty hour', $view['hour']->vars['placeholder']);
        $this->assertSame('', $view['minute']->vars['placeholder']);
        $this->assertSame('Empty second', $view['second']->vars['placeholder']);
    }

    public function testPassPlaceholderAsPartialArrayAddNullIfRequired()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'required' => true,
            'placeholder' => array(
                'hour' => 'Empty hour',
                'second' => 'Empty second',
            ),
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('Empty hour', $view['hour']->vars['placeholder']);
        $this->assertNull($view['minute']->vars['placeholder']);
        $this->assertSame('Empty second', $view['second']->vars['placeholder']);
    }

    public function provideCompoundWidgets()
    {
        return array(
            array('text'),
            array('choice'),
        );
    }

    /**
     * @dataProvider provideCompoundWidgets
     */
    public function testHourErrorsBubbleUp($widget)
    {
        $error = new FormError('Invalid!');
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => $widget,
        ));
        $form['hour']->addError($error);

        $this->assertSame(array(), iterator_to_array($form['hour']->getErrors()));
        $this->assertSame(array($error), iterator_to_array($form->getErrors()));
    }

    /**
     * @dataProvider provideCompoundWidgets
     */
    public function testMinuteErrorsBubbleUp($widget)
    {
        $error = new FormError('Invalid!');
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => $widget,
        ));
        $form['minute']->addError($error);

        $this->assertSame(array(), iterator_to_array($form['minute']->getErrors()));
        $this->assertSame(array($error), iterator_to_array($form->getErrors()));
    }

    /**
     * @dataProvider provideCompoundWidgets
     */
    public function testSecondErrorsBubbleUp($widget)
    {
        $error = new FormError('Invalid!');
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => $widget,
            'with_seconds' => true,
        ));
        $form['second']->addError($error);

        $this->assertSame(array(), iterator_to_array($form['second']->getErrors()));
        $this->assertSame(array($error), iterator_to_array($form->getErrors()));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\InvalidConfigurationException
     */
    public function testInitializeWithSecondsAndWithoutMinutes()
    {
        $this->factory->create(static::TESTED_TYPE, null, array(
            'with_minutes' => false,
            'with_seconds' => true,
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testThrowExceptionIfHoursIsInvalid()
    {
        $this->factory->create(static::TESTED_TYPE, null, array(
            'hours' => 'bad value',
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testThrowExceptionIfMinutesIsInvalid()
    {
        $this->factory->create(static::TESTED_TYPE, null, array(
            'minutes' => 'bad value',
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testThrowExceptionIfSecondsIsInvalid()
    {
        $this->factory->create(static::TESTED_TYPE, null, array(
            'seconds' => 'bad value',
        ));
    }

    public function testPassDefaultChoiceTranslationDomain()
    {
        $form = $this->factory->create(static::TESTED_TYPE);

        $view = $form->createView();
        $this->assertFalse($view['hour']->vars['choice_translation_domain']);
        $this->assertFalse($view['minute']->vars['choice_translation_domain']);
    }

    public function testPassChoiceTranslationDomainAsString()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'choice_translation_domain' => 'messages',
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('messages', $view['hour']->vars['choice_translation_domain']);
        $this->assertSame('messages', $view['minute']->vars['choice_translation_domain']);
        $this->assertSame('messages', $view['second']->vars['choice_translation_domain']);
    }

    public function testPassChoiceTranslationDomainAsArray()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'choice_translation_domain' => array(
                'hour' => 'foo',
                'second' => 'test',
            ),
            'with_seconds' => true,
        ));

        $view = $form->createView();
        $this->assertSame('foo', $view['hour']->vars['choice_translation_domain']);
        $this->assertFalse($view['minute']->vars['choice_translation_domain']);
        $this->assertSame('test', $view['second']->vars['choice_translation_domain']);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        $view = array('hour' => '', 'minute' => '');

        parent::testSubmitNull($expected, $norm, $view);
    }

    public function testSubmitNullUsesDefaultEmptyData($emptyData = array(), $expectedData = null)
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'empty_data' => $emptyData,
        ));
        $form->submit(null);

        // view transformer writes back empty strings in the view data
        $this->assertSame(array('hour' => '', 'minute' => ''), $form->getViewData());
        $this->assertSame($expectedData, $form->getNormData());
        $this->assertSame($expectedData, $form->getData());
    }

    /**
     * @dataProvider provideEmptyData
     */
    public function testSubmitNullUsesDateEmptyData($widget, $emptyData, $expectedData)
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, array(
            'widget' => $widget,
            'empty_data' => $emptyData,
        ));
        $form->submit(null);

        $this->assertSame($emptyData, $form->getViewData());
        $this->assertEquals($expectedData, $form->getNormData());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function provideEmptyData()
    {
        $expectedData = \DateTime::createFromFormat('Y-m-d H:i', '1970-01-01 21:23');

        return array(
            'Simple field' => array('single_text', '21:23', $expectedData),
            'Compound text field' => array('text', array('hour' => '21', 'minute' => '23'), $expectedData),
            'Compound choice field' => array('choice', array('hour' => '21', 'minute' => '23'), $expectedData),
        );
    }
}
