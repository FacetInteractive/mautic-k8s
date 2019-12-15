<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependency;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ListType.
 */
class ListType extends AbstractType
{
    private $translator;
    private $fieldChoices        = [];
    private $timezoneChoices     = [];
    private $countryChoices      = [];
    private $regionChoices       = [];
    private $listChoices         = [];
    private $campaignChoices     = [];
    private $emailChoices        = [];
    private $deviceTypesChoices  = [];
    private $deviceBrandsChoices = [];
    private $deviceOsChoices     = [];
    private $tagChoices          = [];
    private $stageChoices        = [];
    private $assetChoices        = [];
    private $localeChoices       = [];
    private $categoriesChoices   = [];

    /**
     * ListType constructor.
     *
     * @param TranslatorInterface $translator
     * @param ListModel           $listModel
     * @param EmailModel          $emailModel
     * @param CorePermissions     $security
     * @param LeadModel           $leadModel
     * @param StageModel          $stageModel
     * @param CategoryModel       $categoryModel
     * @param UserHelper          $userHelper
     * @param CampaignModel       $campaignModel
     * @param AssetModel          $assetModel
     */
    public function __construct(TranslatorInterface $translator, ListModel $listModel, EmailModel $emailModel, CorePermissions $security, LeadModel $leadModel, StageModel $stageModel, CategoryModel $categoryModel, UserHelper $userHelper, CampaignModel $campaignModel, AssetModel $assetModel)
    {
        $this->translator = $translator;

        $this->fieldChoices = $listModel->getChoiceFields();

        // Locales
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        // Segments
        $lists = $listModel->getUserLists();
        foreach ($lists as $list) {
            $this->listChoices[$list['id']] = $list['name'];
        }

        // Campaigns
        $campaigns = $campaignModel->getPublishedCampaigns(true);
        foreach ($campaigns as $campaign) {
            $this->campaignChoices[$campaign['id']] = $campaign['name'];
        }

        $viewOther   = $security->isGranted('email:emails:viewother');
        $currentUser = $userHelper->getUser();
        $emailRepo   = $emailModel->getRepository();

        $emailRepo->setCurrentUser($currentUser);

        $emails = $emailRepo->getEmailList('', 0, 0, $viewOther, true);

        foreach ($emails as $email) {
            $this->emailChoices[$email['language']][$email['id']] = $email['name'];
        }
        ksort($this->emailChoices);

        // Get assets without 'filter' or 'limit'
        $assets = $assetModel->getLookupResults('asset', null, 0);
        foreach ($assets as $asset) {
            $this->assetChoices[$asset['language']][$asset['id']] = $asset['title'];
        }
        ksort($this->assetChoices);

        $tags = $leadModel->getTagList();
        foreach ($tags as $tag) {
            $this->tagChoices[$tag['value']] = $tag['label'];
        }

        $stages = $stageModel->getRepository()->getSimpleList();
        foreach ($stages as $stage) {
            $this->stageChoices[$stage['value']] = $stage['label'];
        }

        $categories = $categoryModel->getLookupResults('global');

        foreach ($categories as $category) {
            $this->categoriesChoices[$category['id']] = $category['title'];
        }
        $this->deviceTypesChoices  = array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames()));
        $this->deviceBrandsChoices = DeviceParser::$deviceBrands;
        $this->deviceOsChoices     = array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.list', $options));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'alias',
            'text',
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.list.help.alias',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'description',
            'textarea',
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'isGlobal',
            'yesno_button_group',
            [
                'label'      => 'mautic.lead.list.form.isglobal',
                'attr'       => [
                    'tooltip' => 'mautic.lead.list.form.isglobal.tooltip',
                ],
            ]
        );

        $builder->add(
            'isPreferenceCenter',
            'yesno_button_group',
            [
                'label'      => 'mautic.lead.list.form.isPreferenceCenter',
                'attr'       => [
                    'tooltip' => 'mautic.lead.list.form.isPreferenceCenter.tooltip',
                ],
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');

        $filterModalTransformer = new FieldFilterTransformer($this->translator, ['object'=>'lead']);
        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type'    => 'leadlist_filter',
                    'options' => [
                        'label'          => false,
                        'timezones'      => $this->timezoneChoices,
                        'countries'      => $this->countryChoices,
                        'regions'        => $this->regionChoices,
                        'fields'         => $this->fieldChoices,
                        'lists'          => $this->listChoices,
                        'campaign'       => $this->campaignChoices,
                        'emails'         => $this->emailChoices,
                        'deviceTypes'    => $this->deviceTypesChoices,
                        'deviceBrands'   => $this->deviceBrandsChoices,
                        'deviceOs'       => $this->deviceOsChoices,
                        'assets'         => $this->assetChoices,
                        'tags'           => $this->tagChoices,
                        'stage'          => $this->stageChoices,
                        'locales'        => $this->localeChoices,
                        'globalcategory' => $this->categoriesChoices,
                    ],
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'label'          => false,
                    'constraints'    => [
                        new CircularDependency([
                            'message' => 'mautic.core.segment.circular_dependency_exists',
                        ]),
                    ],
                ]
            )->addModelTransformer($filterModalTransformer)
        );

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\LeadBundle\Entity\LeadList',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields']         = $this->fieldChoices;
        $view->vars['countries']      = $this->countryChoices;
        $view->vars['regions']        = $this->regionChoices;
        $view->vars['timezones']      = $this->timezoneChoices;
        $view->vars['lists']          = $this->listChoices;
        $view->vars['campaign']       = $this->campaignChoices;
        $view->vars['emails']         = $this->emailChoices;
        $view->vars['deviceTypes']    = $this->deviceTypesChoices;
        $view->vars['deviceBrands']   = $this->deviceBrandsChoices;
        $view->vars['deviceOs']       = $this->deviceOsChoices;
        $view->vars['assets']         = $this->assetChoices;
        $view->vars['tags']           = $this->tagChoices;
        $view->vars['stage']          = $this->stageChoices;
        $view->vars['locales']        = $this->localeChoices;
        $view->vars['globalcategory'] = $this->categoriesChoices;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadlist';
    }
}
