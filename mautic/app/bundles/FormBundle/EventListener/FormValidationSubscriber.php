<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Form\Type\FormFieldEmailType;
use Mautic\FormBundle\FormEvents;

class FormValidationSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * FormValidationSubscriber constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD    => ['onFormBuilder', 0],
            FormEvents::ON_FORM_VALIDATE => ['onFormValidate', 0],
        ];
    }

    /**
     * Add a simple email form.
     *
     * @param Events\FormBuilderEvent $event
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        $event->addValidator(
            'phone.validation',
            [
                'eventName' => FormEvents::ON_FORM_VALIDATE,
                'fieldType' => 'tel',
                'formType'  => \Mautic\FormBundle\Form\Type\FormFieldTelType::class,
            ]
        );

        if (!empty($this->coreParametersHelper->getParameter('do_not_submit_emails'))) {
            $event->addValidator(
                'email.validation',
                [
                    'eventName' => FormEvents::ON_FORM_VALIDATE,
                    'fieldType' => 'email',
                    'formType'  => FormFieldEmailType::class,
                ]
            );
        }
    }

    /**
     * Custom validation     *.
     *
     *@param Events\ValidationEvent $event
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $value = $event->getValue();

        if (!empty($value)) {
            $this->fieldTelValidation($event);
            $this->fieldEmailValidation($event);
        }
    }

    /**
     * @param Events\ValidationEvent $event
     */
    private function fieldEmailValidation(Events\ValidationEvent $event)
    {
        $field = $event->getField();
        $value = $event->getValue();
        if ($field->getType() === 'email' && !empty($field->getValidation()['donotsubmit'])) {
            // Check the domains using shell wildcard patterns
            $donotSubmitFilter = function ($doNotSubmitArray) use ($value) {
                return fnmatch($doNotSubmitArray, $value, FNM_CASEFOLD);
            };
            $notNotSubmitEmails = $this->coreParametersHelper->getParameter('do_not_submit_emails');
            if (array_filter($notNotSubmitEmails, $donotSubmitFilter)) {
                $event->failedValidation(ArrayHelper::getValue('donotsubmit_validationmsg', $field->getValidation()));
            }
        }
    }

    /**
     * @param Events\ValidationEvent $event
     */
    private function fieldTelValidation(Events\ValidationEvent $event)
    {
        $field = $event->getField();
        $value = $event->getValue();

        if ($field->getType() === 'tel' && !empty($field->getValidation()['international'])) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
            } catch (NumberParseException $e) {
                if (!empty($field->getValidation()['international_validationmsg'])) {
                    $event->failedValidation($field->getValidation()['international_validationmsg']);
                } else {
                    $event->failedValidation($this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'));
                }
            }
        }
    }
}
