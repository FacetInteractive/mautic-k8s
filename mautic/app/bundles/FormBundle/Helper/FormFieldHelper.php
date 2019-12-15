<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\FormBundle\Entity\Field;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FormFieldHelper.
 */
class FormFieldHelper extends AbstractFormFieldHelper
{
    /**
     * @var ValidatorInterface|\Symfony\Component\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var array
     */
    private $types = [
        'captcha' => [
            'constraints' => [
                NotBlank::class => ['message' => 'mautic.form.submission.captcha.invalid'],

                EqualTo::class => ['message' => 'mautic.form.submission.captcha.invalid'],

                Blank::class => ['message' => 'mautic.form.submission.captcha.invalid'],
            ],
        ],
        'checkboxgrp' => [],
        'country'     => [],
        'date'        => [],
        'datetime'    => [],
        'email'       => [
            'filter'      => 'email',
            'constraints' => [
                Email::class => ['message' => 'mautic.form.submission.email.invalid'],
            ],
        ],
        'freetext' => [],
        'freehtml' => [],
        'hidden'   => [],
        'number'   => [
            'filter' => 'float',
        ],
        'pagebreak' => [],
        'password'  => [],
        'radiogrp'  => [],
        'select'    => [],
        'tel'       => [],
        'text'      => [],
        'textarea'  => [],
        'url'       => [
            'filter'      => 'url',
            'constraints' => [
                Url::class => ['message' => 'mautic.form.submission.url.invalid'],
            ],
        ],
        'file' => [],
    ];

    /**
     * FormFieldHelper constructor.
     *
     * @param TranslatorInterface $translator
     * @param ValidatorInterface  $validator
     */
    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator = null)
    {
        $this->translator = $translator;

        if (null === $validator) {
            $validator = $validator = Validation::createValidator();
        }
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * Set the translation key prefix.
     */
    public function setTranslationKeyPrefix()
    {
        $this->translationKeyPrefix = 'mautic.form.field.type.';
    }

    /**
     * @param array $customFields
     *
     * @deprecated  to be removed in 3.0; use getChoiceList($customFields = []) instead
     *
     * @return array
     */
    public function getList($customFields = [])
    {
        return $this->getChoiceList($customFields);
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get fields input filter.
     *
     * @param $type
     *
     * @return string
     */
    public function getFieldFilter($type)
    {
        if (array_key_exists($type, $this->types)) {
            if (isset($this->types[$type]['filter'])) {
                return $this->types[$type]['filter'];
            }

            return 'clean';
        }

        return 'alphanum';
    }

    /**
     * @param       $type
     * @param       $value
     * @param Field $f
     *
     * @return array
     */
    public function validateFieldValue($type, $value, $f = null)
    {
        $errors = [];
        if (isset($this->types[$type]['constraints'])) {
            foreach ($this->types[$type]['constraints'] as $constraint => $opts) {
                //don't check empty values unless the constraint is NotBlank
                if (NotBlank::class === $constraint && empty($value)) {
                    continue;
                }

                if ($type == 'captcha') {
                    $captcha = $f->getProperties()['captcha'];
                    if (empty($captcha) && Blank::class !== $constraint) {
                        // Used as a honeypot
                        $captcha = '';
                    } elseif (Blank::class === $constraint) {
                        continue;
                    }

                    if (EqualTo::class == $constraint) {
                        $opts['value'] = $captcha;
                    }
                }

                /** @var ConstraintViolationList $violations */
                $violations = $this->validator->validate($value, new $constraint($opts));

                if (count($violations)) {
                    /** @var ConstraintViolation $v */
                    foreach ($violations as $v) {
                        $transParameters = $v->getParameters();

                        if ($f !== null) {
                            $transParameters['%label%'] = '&quot;'.$f->getLabel().'&quot;';
                        }

                        $errors[] = $this->translator->trans($v->getMessage(), $transParameters, 'validators');
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param $field
     * @param $value
     * @param $formName
     * @param $formHtml
     */
    public function populateField($field, $value, $formName, &$formHtml)
    {
        $alias = $field->getAlias();

        switch ($field->getType()) {
            case 'text':
            case 'email':
            case 'hidden':
                if (preg_match('/<input(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)value="(.*?)"(.*?)\/>/i', $formHtml, $match)) {
                    $replace = '<input'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[2].'value="'.$this->sanitizeValue($value).'"'
                        .$match[4].'/>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'textarea':
                if (preg_match('/<textarea(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)>(.*?)<\/textarea>/i', $formHtml, $match)) {
                    $replace  = '<textarea'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[2].'>'.$this->sanitizeValue($value).'</textarea>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'checkboxgrp':
                if (is_string($value) && strrpos($value, '|') > 0) {
                    $value = explode('|', $value);
                } elseif (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $val) {
                    $val = $this->sanitizeValue($val);
                    if (preg_match(
                        '/<input(.*?)id="mauticform_checkboxgrp_checkbox_'.$alias.'(.*?)"(.*?)value="'.$val.'"(.*?)\/>/i',
                        $formHtml,
                        $match
                    )) {
                        $replace = '<input'.$match[1].'id="mauticform_checkboxgrp_checkbox_'.$alias.$match[2].'"'.$match[3].'value="'.$val.'"'
                            .$match[4].' checked />';
                        $formHtml = str_replace($match[0], $replace, $formHtml);
                    }
                }
                break;
            case 'radiogrp':
                $value = $this->sanitizeValue($value);
                if (preg_match('/<input(.*?)id="mauticform_radiogrp_radio_'.$alias.'(.*?)"(.*?)value="'.$value.'"(.*?)\/>/i', $formHtml, $match)) {
                    $replace = '<input'.$match[1].'id="mauticform_radiogrp_radio_'.$alias.$match[2].'"'.$match[3].'value="'.$value.'"'.$match[4]
                        .' checked />';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'select':
            case 'country':
                $regex = '/<select\s*id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)<\/select>/is';
                if (preg_match($regex, $formHtml, $match)) {
                    $origText = $match[0];
                    $replace  = str_replace(
                        '<option value="'.$this->sanitizeValue($value).'">',
                        '<option value="'.$this->sanitizeValue($value).'" selected="selected">',
                        $origText
                    );
                    $formHtml = str_replace($origText, $replace, $formHtml);
                }

                break;
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function sanitizeValue($value)
    {
        $valueType = gettype($value);
        $value     = str_replace(['"', '>', '<'], ['&quot;', '&gt;', '&lt;'], strip_tags(urldecode($value)));
        // for boolean expect 0 or 1
        if ($valueType === 'boolean') {
            return (int) $value;
        }

        return $value;
    }
}
