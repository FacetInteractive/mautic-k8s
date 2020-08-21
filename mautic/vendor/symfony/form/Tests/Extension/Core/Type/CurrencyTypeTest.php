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
use Symfony\Component\Intl\Util\IntlTestHelper;

class CurrencyTypeTest extends BaseTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\CurrencyType';

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    /**
     * @group legacy
     */
    public function testLegacyName()
    {
        $form = $this->factory->create('currency');

        $this->assertSame('currency', $form->getConfig()->getType()->getName());
    }

    public function testCurrenciesAreSelectable()
    {
        $choices = $this->factory->create(static::TESTED_TYPE)
            ->createView()->vars['choices'];

        $this->assertContains(new ChoiceView('EUR', 'EUR', 'Euro'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('USD', 'USD', 'US Dollar'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('SIT', 'SIT', 'Slovenian Tolar'), $choices, '', false, false);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        parent::testSubmitNull($expected, $norm, '');
    }
}
