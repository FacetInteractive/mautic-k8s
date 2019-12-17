<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * ConfigType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data'] as $config) {
            if (isset($config['formAlias']) && !empty($config['parameters'])) {
                $checkThese = array_intersect(array_keys($config['parameters']), $options['fileFields']);
                foreach ($checkThese as $checkMe) {
                    // Unset base64 encoded values
                    unset($config['parameters'][$checkMe]);
                }
                $builder->add(
                    $config['formAlias'],
                    $config['formAlias'],
                    [
                        'data' => $config['parameters'],
                    ]
                );
            }
        }

        $translator = $this->translator;
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $translator) {
                $form = $event->getForm();

                foreach ($form as $config => $configForm) {
                    foreach ($configForm as $key => $child) {
                        if (in_array($key, $options['doNotChange'])) {
                            if ($options['doNotChangeDisplayMode'] == 'mask') {
                                $fieldOptions = $child->getConfig()->getOptions();

                                $configForm->add(
                                    $key,
                                    'text',
                                    [
                                        'label'    => $fieldOptions['label'],
                                        'required' => false,
                                        'mapped'   => false,
                                        'disabled' => true,
                                        'attr'     => [
                                            'placeholder' => $translator->trans('mautic.config.restricted'),
                                            'class'       => 'form-control',
                                        ],
                                        'label_attr' => ['class' => 'control-label'],
                                    ]
                                );
                            } elseif ($options['doNotChangeDisplayMode'] == 'remove') {
                                $configForm->remove($key);
                            }
                        }
                    }
                }
            }
        );

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'apply_onclick' => 'Mautic.activateBackdrop()',
                'save_onclick'  => 'Mautic.activateBackdrop()',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'doNotChange',
                'doNotChangeDisplayMode',
            ]
        );

        $resolver->setDefaults(
            [
                'fileFields' => [],
            ]
        );
    }
}
