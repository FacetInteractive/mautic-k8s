<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\Core\View;

/**
 * Represents a choice in templates.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.7, to be removed in 3.0.
 *             Use {@link \Symfony\Component\Form\ChoiceList\View\ChoiceView} instead.
 */
class ChoiceView
{
    public $label;
    public $value;
    public $data;

    /**
     * Creates a new ChoiceView.
     *
     * @param mixed  $data  The original choice
     * @param string $value The view representation of the choice
     * @param string $label The label displayed to humans
     */
    public function __construct($data, $value, $label)
    {
        $this->data = $data;
        $this->value = $value;
        $this->label = $label;
    }
}

namespace Symfony\Component\Form\ChoiceList\View;

use Symfony\Component\Form\Extension\Core\View\ChoiceView as LegacyChoiceView;

/**
 * Represents a choice in templates.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ChoiceView extends LegacyChoiceView
{
    /**
     * Additional attributes for the HTML tag.
     */
    public $attr;

    /**
     * Creates a new choice view.
     *
     * @param mixed  $data  The original choice
     * @param string $value The view representation of the choice
     * @param string $label The label displayed to humans
     * @param array  $attr  Additional attributes for the HTML tag
     */
    public function __construct($data, $value, $label, array $attr = array())
    {
        parent::__construct($data, $value, $label);

        $this->attr = $attr;
    }
}
