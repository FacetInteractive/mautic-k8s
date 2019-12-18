<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyChangeLog;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Event\CategoryChangeEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Intl\Intl;

/**
 * Class LeadModel
 * {@inheritdoc}
 */
class LeadModel extends FormModel
{
    use DefaultValueTrait, OperatorListTrait, RequestTrait;

    const CHANNEL_FEATURE = 'contact_preference';

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var array
     */
    protected $leadFields = [];

    /**
     * @var ListModel
     */
    protected $leadListModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var CategoryModel
     */
    protected $categoryModel;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var ChannelListHelper
     */
    protected $channelListHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var UserProvider
     */
    protected $userProvider;

    /**
     * @var
     */
    protected $leadTrackingId;

    /**
     * @var bool
     */
    protected $leadTrackingCookieGenerated = false;

    /**
     * @var array
     */
    protected $availableLeadFields = [];

    /**
     * @var EmailValidator
     */
    protected $emailValidator;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * @var DeviceTracker
     */
    private $deviceTracker;

    /**
     * @var LegacyLeadModel
     */
    private $legacyLeadModel;

    /**
     * @var IpAddressModel
     */
    private $ipAddressModel;

    /**
     * @var bool
     */
    private $repoSetup = false;

    /**
     * @var array
     */
    private $flattenedFields = [];

    /**
     * @var array
     */
    private $fieldsByGroup = [];

    /**
     * @param RequestStack         $requestStack
     * @param CookieHelper         $cookieHelper
     * @param IpLookupHelper       $ipLookupHelper
     * @param PathsHelper          $pathsHelper
     * @param IntegrationHelper    $integrationHelper
     * @param FieldModel           $leadFieldModel
     * @param ListModel            $leadListModel
     * @param FormFactory          $formFactory
     * @param CompanyModel         $companyModel
     * @param CategoryModel        $categoryModel
     * @param ChannelListHelper    $channelListHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param EmailValidator       $emailValidator
     * @param UserProvider         $userProvider
     * @param ContactTracker       $contactTracker
     * @param DeviceTracker        $deviceTracker
     * @param LegacyLeadModel      $legacyLeadModel
     * @param IpAddressModel       $ipAddressModel
     */
    public function __construct(
        RequestStack $requestStack,
        CookieHelper $cookieHelper,
        IpLookupHelper $ipLookupHelper,
        PathsHelper $pathsHelper,
        IntegrationHelper $integrationHelper,
        FieldModel $leadFieldModel,
        ListModel $leadListModel,
        FormFactory $formFactory,
        CompanyModel $companyModel,
        CategoryModel $categoryModel,
        ChannelListHelper $channelListHelper,
        CoreParametersHelper $coreParametersHelper,
        EmailValidator $emailValidator,
        UserProvider $userProvider,
        ContactTracker $contactTracker,
        DeviceTracker $deviceTracker,
        LegacyLeadModel $legacyLeadModel,
        IpAddressModel $ipAddressModel
    ) {
        $this->request              = $requestStack->getCurrentRequest();
        $this->cookieHelper         = $cookieHelper;
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->pathsHelper          = $pathsHelper;
        $this->integrationHelper    = $integrationHelper;
        $this->leadFieldModel       = $leadFieldModel;
        $this->leadListModel        = $leadListModel;
        $this->companyModel         = $companyModel;
        $this->formFactory          = $formFactory;
        $this->categoryModel        = $categoryModel;
        $this->channelListHelper    = $channelListHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->emailValidator       = $emailValidator;
        $this->userProvider         = $userProvider;
        $this->contactTracker       = $contactTracker;
        $this->deviceTracker        = $deviceTracker;
        $this->legacyLeadModel      = $legacyLeadModel;
        $this->ipAddressModel       = $ipAddressModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\LeadRepository
     */
    public function getRepository()
    {
        $repo = $this->em->getRepository('MauticLeadBundle:Lead');
        $repo->setDispatcher($this->dispatcher);

        if (!$this->repoSetup) {
            $this->repoSetup = true;

            //set the point trigger model in order to get the color code for the lead
            $fields = $this->leadFieldModel->getFieldList(true, false);

            $socialFields = (!empty($fields['social'])) ? array_keys($fields['social']) : [];
            $repo->setAvailableSocialFields($socialFields);

            $searchFields = [];
            foreach ($fields as $group => $groupFields) {
                $searchFields = array_merge($searchFields, array_keys($groupFields));
            }
            $repo->setAvailableSearchFields($searchFields);
        }

        return $repo;
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\TagRepository
     */
    public function getTagRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Tag');
    }

    /**
     * @return \Mautic\LeadBundle\Entity\PointsChangeLogRepository
     */
    public function getPointLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:PointsChangeLog');
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\UtmTagRepository
     */
    public function getUtmTagRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:UtmTag');
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadDeviceRepository
     */
    public function getDeviceRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadDevice');
    }

    /**
     * Get the lead event log repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadEventLogRepository
     */
    public function getEventLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadEventLog');
    }

    /**
     * Get the frequency rules repository.
     *
     * @return \Mautic\LeadBundle\Entity\FrequencyRuleRepository
     */
    public function getFrequencyRuleRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:FrequencyRule');
    }

    /**
     * Get the Stages change log repository.
     *
     * @return \Mautic\LeadBundle\Entity\StagesChangeLogRepository
     */
    public function getStagesChangeLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:StagesChangeLog');
    }

    /**
     * Get the lead categories repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadCategoryRepository
     */
    public function getLeadCategoryRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadCategory');
    }

    /**
     * @return \Mautic\LeadBundle\Entity\MergeRecordRepository
     */
    public function getMergeRecordRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:MergeRecord');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:leads';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getNameGetter()
    {
        return 'getPrimaryIdentifier';
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead                                $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('lead', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|Lead
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Lead();
        }

        $entity = parent::getEntity($id);

        if (null === $entity) {
            // Check if this contact was merged into another and if so, return the new contact
            if ($entity = $this->getMergeRecordRepository()->findMergedContact($id)) {
                // Hydrate fields with custom field data
                $fields = $this->getRepository()->getFieldValues($entity->getId());
                $entity->setFields($fields);
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::LEAD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::LEAD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::LEAD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::LEAD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead $entity
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        $companyFieldMatches = [];
        $fields              = $entity->getFields();
        $company             = null;

        //check to see if we can glean information from ip address
        if (!$entity->imported && count($ips = $entity->getIpAddresses())) {
            $details = $ips->first()->getIpDetails();
            // Only update with IP details if none of the following are set to prevent wrong combinations
            if (empty($fields['core']['city']['value']) && empty($fields['core']['state']['value']) && empty($fields['core']['country']['value']) && empty($fields['core']['zipcode']['value'])) {
                if (!empty($details['city'])) {
                    $entity->addUpdatedField('city', $details['city']);
                    $companyFieldMatches['city'] = $details['city'];
                }

                if (!empty($details['region'])) {
                    $entity->addUpdatedField('state', $details['region']);
                    $companyFieldMatches['state'] = $details['region'];
                }

                if (!empty($details['country'])) {
                    $entity->addUpdatedField('country', $details['country']);
                    $companyFieldMatches['country'] = $details['country'];
                }

                if (!empty($details['zipcode'])) {
                    $entity->addUpdatedField('zipcode', $details['zipcode']);
                }
            }

            if (!$entity->getCompany() && !empty($details['organization']) && $this->coreParametersHelper->getParameter('ip_lookup_create_organization', false)) {
                $entity->addUpdatedField('company', $details['organization']);
            }
        }

        $updatedFields = $entity->getUpdatedFields();
        if (isset($updatedFields['company'])) {
            $companyFieldMatches['company']            = $updatedFields['company'];
            list($company, $leadAdded, $companyEntity) = IdentifyCompanyHelper::identifyLeadsCompany($companyFieldMatches, $entity, $this->companyModel);
            if ($leadAdded) {
                $entity->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            }
        }

        $this->processManipulator($entity);

        $this->setEntityDefaultValues($entity);

        $this->ipAddressModel->saveIpAddressesReferencesForContact($entity);

        parent::saveEntity($entity, $unlock);

        if (!empty($company)) {
            // Save after the lead in for new leads created through the API and maybe other places
            $this->companyModel->addLeadToCompany($companyEntity, $entity);
            $this->setPrimaryCompany($companyEntity->getId(), $entity->getId());
        }

        $this->em->clear(CompanyChangeLog::class);
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        // Delete custom avatar if one exists
        $imageDir = $this->pathsHelper->getSystemPath('images', true);
        $avatar   = $imageDir.'/lead_avatars/avatar'.$entity->getId();

        if (file_exists($avatar)) {
            unlink($avatar);
        }

        parent::deleteEntity($entity);
    }

    /**
     * Clear all Lead entities.
     */
    public function clearEntities()
    {
        $this->getRepository()->clear();
    }

    /**
     * Populates custom field values for updating the lead. Also retrieves social media data.
     *
     * @param Lead       $lead
     * @param array      $data
     * @param bool|false $overwriteWithBlank
     * @param bool|true  $fetchSocialProfiles
     * @param bool|false $bindWithForm        Send $data through the Lead form and only use valid data (should be used with request data)
     *
     * @return array
     */
    public function setFieldValues(Lead $lead, array $data, $overwriteWithBlank = false, $fetchSocialProfiles = true, $bindWithForm = false)
    {
        if ($fetchSocialProfiles) {
            //@todo - add a catch to NOT do social gleaning if a lead is created via a form, etc as we do not want the user to experience the wait
            //generate the social cache
            list($socialCache, $socialFeatureSettings) = $this->integrationHelper->getUserProfiles(
                $lead,
                $data,
                true,
                null,
                false,
                true
            );

            //set the social cache while we have it
            if (!empty($socialCache)) {
                $lead->setSocialCache($socialCache);
            }
        }

        if (isset($data['stage'])) {
            $stagesChangeLogRepo = $this->getStagesChangeLogRepository();
            $currentLeadStage    = $stagesChangeLogRepo->getCurrentLeadStage($lead->getId());

            $previousId = is_object($data['stage']) ? $data['stage']->getId() : (int) $data['stage'];
            if ($previousId !== $currentLeadStage) {
                $stage = $this->em->getRepository('MauticStageBundle:Stage')->find($data['stage']);
                $lead->stageChangeLogEntry(
                    $stage,
                    $stage->getId().':'.$stage->getName(),
                    $this->translator->trans('mautic.stage.event.changed')
                );
            }
        }

        //save the field values
        $fieldValues = $lead->getFields();

        if (empty($fieldValues) || $bindWithForm) {
            // Lead is new or they haven't been populated so let's build the fields now
            if (empty($this->flattenedFields)) {
                $this->flattenedFields = $this->leadFieldModel->getEntities(
                    [
                        'filter'         => ['isPublished' => true, 'object' => 'lead'],
                        'hydration_mode' => 'HYDRATE_ARRAY',
                    ]
                );
                $this->fieldsByGroup = $this->organizeFieldsByGroup($this->flattenedFields);
            }

            if (empty($fieldValues)) {
                $fieldValues = $this->fieldsByGroup;
            }
        }

        if ($bindWithForm) {
            // Cleanup the field values
            $form = $this->createForm(
                new Lead(), // use empty lead to prevent binding errors
                $this->formFactory,
                null,
                ['fields' => $this->flattenedFields, 'csrf_protection' => false, 'allow_extra_fields' => true]
            );

            // Unset stage and owner from the form because it's already been handled
            unset($data['stage'], $data['owner'], $data['tags']);
            // Prepare special fields
            $this->prepareParametersFromRequest($form, $data, $lead);
            // Submit the data
            $form->submit($data);

            if ($form->getErrors()->count()) {
                $this->logger->addDebug('LEAD: form validation failed with an error of '.(string) $form->getErrors());
            }
            foreach ($form as $field => $formField) {
                if (isset($data[$field])) {
                    if ($formField->getErrors()->count()) {
                        $this->logger->addDebug('LEAD: '.$field.' failed form validation with an error of '.(string) $formField->getErrors());
                        // Don't save bad data
                        unset($data[$field]);
                    } else {
                        $data[$field] = $formField->getData();
                    }
                }
            }
        }

        //update existing values
        foreach ($fieldValues as $group => &$groupFields) {
            foreach ($groupFields as $alias => &$field) {
                if (!isset($field['value'])) {
                    $field['value'] = null;
                }

                // Only update fields that are part of the passed $data array
                if (array_key_exists($alias, $data)) {
                    if (!$bindWithForm) {
                        $this->cleanFields($data, $field);
                    }
                    $curValue = $field['value'];
                    $newValue = isset($data[$alias]) ? $data[$alias] : '';

                    if (is_array($newValue)) {
                        $newValue = implode('|', $newValue);
                    }

                    $isEmpty = (null === $newValue || '' === $newValue);
                    if ($curValue !== $newValue && (!$isEmpty || ($isEmpty && $overwriteWithBlank))) {
                        $field['value'] = $newValue;
                        $lead->addUpdatedField($alias, $newValue, $curValue);
                    }

                    //if empty, check for social media data to plug the hole
                    if (empty($newValue) && !empty($socialCache)) {
                        foreach ($socialCache as $service => $details) {
                            //check to see if a field has been assigned

                            if (!empty($socialFeatureSettings[$service]['leadFields'])
                                && in_array($field['alias'], $socialFeatureSettings[$service]['leadFields'])
                            ) {
                                //check to see if the data is available
                                $key = array_search($field['alias'], $socialFeatureSettings[$service]['leadFields']);
                                if (isset($details['profile'][$key])) {
                                    //Found!!
                                    $field['value'] = $details['profile'][$key];
                                    $lead->addUpdatedField($alias, $details['profile'][$key]);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $lead->setFields($fieldValues);
    }

    /**
     * Disassociates a user from leads.
     *
     * @param $userId
     */
    public function disassociateOwner($userId)
    {
        $leads = $this->getRepository()->findByOwner($userId);
        foreach ($leads as $lead) {
            $lead->setOwner(null);
            $this->saveEntity($lead);
        }
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @param $start
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0)
    {
        $results = [];
        switch ($type) {
            case 'user':
                $results = $this->em->getRepository('MauticUserBundle:User')->getUserList($filter, $limit, $start, ['lead' => 'leads']);
                break;
        }

        return $results;
    }

    /**
     * Obtain an array of users for api lead edits.
     *
     * @return mixed
     */
    public function getOwnerList()
    {
        $results = $this->em->getRepository('MauticUserBundle:User')->getUserList('', 0);

        return $results;
    }

    /**
     * Obtains a list of leads based off IP.
     *
     * @param $ip
     *
     * @return mixed
     */
    public function getLeadsByIp($ip)
    {
        return $this->getRepository()->getLeadsByIp($ip);
    }

    /**
     * Obtains a list of leads based a list of IDs.
     *
     * @param array $ids
     *
     * @return Paginator
     */
    public function getLeadsByIds(array $ids)
    {
        return $this->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param Lead $contact
     *
     * @return bool
     */
    public function canEditContact(Lead $contact)
    {
        return $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser());
    }

    /**
     * Gets the details of a lead if not already set.
     *
     * @param $lead
     *
     * @return mixed
     */
    public function getLeadDetails($lead)
    {
        if ($lead instanceof Lead) {
            $fields = $lead->getFields();
            if (!empty($fields)) {
                return $fields;
            }
        }

        $leadId = ($lead instanceof Lead) ? $lead->getId() : (int) $lead;

        return $this->getRepository()->getFieldValues($leadId);
    }

    /**
     * Reorganizes a field list to be keyed by field's group then alias.
     *
     * @param $fields
     *
     * @return array
     */
    public function organizeFieldsByGroup($fields)
    {
        $array = [];

        foreach ($fields as $field) {
            if ($field instanceof LeadField) {
                $alias = $field->getAlias();
                if ($field->isPublished() and $field->getObject() === 'Lead') {
                    $group                          = $field->getGroup();
                    $array[$group][$alias]['id']    = $field->getId();
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field->getLabel();
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field->getType();
                }
            } else {
                $alias = $field['alias'];
                if ($field['isPublished'] and $field['object'] === 'lead') {
                    $group                          = $field['group'];
                    $array[$group][$alias]['id']    = $field['id'];
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field['label'];
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field['type'];
                }
            }
        }

        //make sure each group key is present
        $groups = ['core', 'social', 'personal', 'professional'];
        foreach ($groups as $g) {
            if (!isset($array[$g])) {
                $array[$g] = [];
            }
        }

        return $array;
    }

    /**
     * Takes leads organized by group and flattens them into just alias => value.
     *
     * @param $fields
     *
     * @deprecated 2.0 to be removed in 3.0 - Use the Lead entity's getProfileFields() instead
     *
     * @return array
     */
    public function flattenFields($fields)
    {
        $flat = [];
        foreach ($fields as $group => $fields) {
            foreach ($fields as $field) {
                $flat[$field['alias']] = $field['value'];
            }
        }

        return $flat;
    }

    /**
     * Returns flat array for single lead.
     *
     * @param $leadId
     *
     * @return array
     */
    public function getLead($leadId)
    {
        return $this->getRepository()->getLead($leadId);
    }

    /**
     * Get the lead from request (ct/clickthrough) and handles auto merging of lead data from request parameters.
     *
     * @deprecated - here till all lead methods are converted to contact methods; preferably use getContactFromRequest instead
     *
     * @param array $queryFields
     *
     * @return array|Lead|null
     */
    public function getLeadFromRequest(array $queryFields = [])
    {
        return $this->getContactFromRequest($queryFields);
    }

    /**
     * Get the contat from request (ct/clickthrough) and handles auto merging of contact data from request parameters.
     *
     * @param array $queryFields
     *
     * @return array|Lead|null
     */
    public function getContactFromRequest($queryFields = [])
    {
        // @todo Instantiate here until we can remove circular dependency on LeadModel in order to make it a service
        $requestStack        = new RequestStack();
        $requestStack->push($this->request);
        $contactRequestHelper = new ContactRequestHelper(
            $this,
            $this->contactTracker,
            $this->coreParametersHelper,
            $this->ipLookupHelper,
            $this->getDeviceRepository(),
            $requestStack,
            $this->logger,
            $this->dispatcher
        );

        return $contactRequestHelper->getContactFromQuery($queryFields);
    }

    /**
     * @param array     $queryFields
     * @param Lead|null $lead
     * @param bool      $returnWithQueryFields
     *
     * @return array|Lead
     */
    public function checkForDuplicateContact(array $queryFields, Lead $lead = null, $returnWithQueryFields = false, $onlyPubliclyUpdateable = false)
    {
        // Search for lead by request and/or update lead fields if some data were sent in the URL query
        if (empty($this->availableLeadFields)) {
            $filter = ['isPublished' => true, 'object' => 'lead'];

            if ($onlyPubliclyUpdateable) {
                $filter['isPubliclyUpdatable'] = true;
            }

            $this->availableLeadFields = $this->leadFieldModel->getFieldList(
                false,
                false,
                $filter
            );
        }

        if (is_null($lead)) {
            $lead = new Lead();
        }

        $uniqueFields    = $this->leadFieldModel->getUniqueIdentifierFields();
        $uniqueFieldData = [];
        $inQuery         = array_intersect_key($queryFields, $this->availableLeadFields);
        $values          = $onlyPubliclyUpdateable ? $inQuery : $queryFields;

        // Run values through setFieldValues to clean them first
        $this->setFieldValues($lead, $values, false, false);
        $cleanFields = $lead->getFields();

        foreach ($inQuery as $k => $v) {
            if (empty($queryFields[$k])) {
                unset($inQuery[$k]);
            }
        }

        foreach ($cleanFields as $group) {
            foreach ($group as $key => $field) {
                if (array_key_exists($key, $uniqueFields) && !empty($field['value'])) {
                    $uniqueFieldData[$key] = $field['value'];
                }
            }
        }

        // Check for leads using unique identifier
        if (count($uniqueFieldData)) {
            $existingLeads = $this->getRepository()->getLeadsByUniqueFields($uniqueFieldData, ($lead) ? $lead->getId() : null);

            if (!empty($existingLeads)) {
                $this->logger->addDebug("LEAD: Existing contact ID# {$existingLeads[0]->getId()} found through query identifiers.");
                // Merge with existing lead or use the one found
                $lead = ($lead->getId()) ? $this->mergeLeads($lead, $existingLeads[0]) : $existingLeads[0];
            }
        }

        return $returnWithQueryFields ? [$lead, $inQuery] : $lead;
    }

    /**
     * Get a list of segments this lead belongs to.
     *
     * @param Lead $lead
     * @param bool $forLists
     * @param bool $arrayHydration
     * @param bool $isPublic
     *
     * @return mixed
     */
    public function getLists(Lead $lead, $forLists = false, $arrayHydration = false, $isPublic = false, $isPreferenceCenter = false)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');

        return $repo->getLeadLists($lead->getId(), $forLists, $arrayHydration, $isPublic, $isPreferenceCenter);
    }

    /**
     * Get a list of companies this contact belongs to.
     *
     * @param Lead $lead
     *
     * @return mixed
     */
    public function getCompanies(Lead $lead)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:CompanyLead');

        return $repo->getCompaniesByLeadId($lead->getId());
    }

    /**
     * Add lead to lists.
     *
     * @param array|Lead     $lead
     * @param array|LeadList $lists
     * @param bool           $manuallyAdded
     */
    public function addToLists($lead, $lists, $manuallyAdded = true)
    {
        $this->leadListModel->addLead($lead, $lists, $manuallyAdded);
    }

    /**
     * Remove lead from lists.
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyRemoved
     */
    public function removeFromLists($lead, $lists, $manuallyRemoved = true)
    {
        $this->leadListModel->removeLead($lead, $lists, $manuallyRemoved);
    }

    /**
     * Add lead to Stage.
     *
     * @param array|Lead  $lead
     * @param array|Stage $stage
     * @param bool        $manuallyAdded
     *
     * @return $this
     */
    public function addToStages($lead, $stage, $manuallyAdded = true)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }
        $lead->setStage($stage);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.added.batch')
        );

        return $this;
    }

    /**
     * Remove lead from Stage.
     *
     * @param      $lead
     * @param      $stage
     * @param bool $manuallyRemoved
     *
     * @return $this
     */
    public function removeFromStages($lead, $stage, $manuallyRemoved = true)
    {
        $lead->setStage(null);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.removed.batch')
        );

        return $this;
    }

    /**
     * @depreacated 2.6.0 to be removed in 3.0; use getFrequencyRules() instead
     *
     * @param Lead $lead
     * @param null $channel
     *
     * @return mixed
     */
    public function getFrequencyRule(Lead $lead, $channel = null)
    {
        return $this->getFrequencyRules($lead, $channel);
    }

    /**
     * @param Lead   $lead
     * @param string $channel
     *
     * @return mixed
     */
    public function getFrequencyRules(Lead $lead, $channel = null)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\FrequencyRuleRepository $frequencyRuleRepo */
        $frequencyRuleRepo = $this->em->getRepository('MauticLeadBundle:FrequencyRule');
        $frequencyRules    = $frequencyRuleRepo->getFrequencyRules($channel, $lead->getId());

        if (empty($frequencyRules)) {
            return [];
        }

        return $frequencyRules;
    }

    /**
     * Set frequency rules for lead per channel.
     *
     * @param Lead $lead
     * @param null $data
     * @param null $leadLists
     *
     * @return bool Returns true
     */
    public function setFrequencyRules(Lead $lead, $data = null, $leadLists = null, $persist = true)
    {
        // One query to get all the lead's current frequency rules and go ahead and create entities for them
        $frequencyRules = $lead->getFrequencyRules()->toArray();
        $entities       = [];
        $channels       = $this->getPreferenceChannels();

        foreach ($channels as $ch) {
            if (empty($data['lead_channels']['preferred_channel'])) {
                $data['lead_channels']['preferred_channel'] = $ch;
            }

            $frequencyRule = (isset($frequencyRules[$ch])) ? $frequencyRules[$ch] : new FrequencyRule();
            $frequencyRule->setChannel($ch);
            $frequencyRule->setLead($lead);
            $frequencyRule->setDateAdded(new \DateTime());

            if (!empty($data['lead_channels']['frequency_number_'.$ch]) && !empty($data['lead_channels']['frequency_time_'.$ch])) {
                $frequencyRule->setFrequencyNumber($data['lead_channels']['frequency_number_'.$ch]);
                $frequencyRule->setFrequencyTime($data['lead_channels']['frequency_time_'.$ch]);
            } else {
                $frequencyRule->setFrequencyNumber(null);
                $frequencyRule->setFrequencyTime(null);
            }

            $frequencyRule->setPauseFromDate(!empty($data['lead_channels']['contact_pause_start_date_'.$ch]) ? $data['lead_channels']['contact_pause_start_date_'.$ch] : null);
            $frequencyRule->setPauseToDate(!empty($data['lead_channels']['contact_pause_end_date_'.$ch]) ? $data['lead_channels']['contact_pause_end_date_'.$ch] : null);

            $frequencyRule->setLead($lead);
            $frequencyRule->setPreferredChannel($data['lead_channels']['preferred_channel'] === $ch);

            if ($persist) {
                $entities[$ch] = $frequencyRule;
            } else {
                $lead->addFrequencyRule($frequencyRule);
            }
        }

        if (!empty($entities)) {
            $this->em->getRepository('MauticLeadBundle:FrequencyRule')->saveEntities($entities);
        }

        foreach ($data['lead_lists'] as $leadList) {
            if (!isset($leadLists[$leadList])) {
                $this->addToLists($lead, [$leadList]);
            }
        }
        // Delete lists that were removed
        $deletedLists = array_diff(array_keys($leadLists), $data['lead_lists']);
        if (!empty($deletedLists)) {
            $this->removeFromLists($lead, $deletedLists);
        }

        if (!empty($data['global_categories'])) {
            $this->addToCategory($lead, $data['global_categories']);
        }
        $leadCategories = $this->getLeadCategories($lead);
        // Delete categories that were removed
        $deletedCategories = array_diff($leadCategories, $data['global_categories']);

        if (!empty($deletedCategories)) {
            $this->removeFromCategories($deletedCategories);
        }

        // Delete channels that were removed
        $deleted = array_diff_key($frequencyRules, $entities);
        if (!empty($deleted)) {
            $this->em->getRepository('MauticLeadBundle:FrequencyRule')->deleteEntities($deleted);
        }

        return true;
    }

    /**
     * @param Lead $lead
     * @param $categories
     * @param bool $manuallyAdded
     *
     * @return array
     */
    public function addToCategory(Lead $lead, $categories, $manuallyAdded = true)
    {
        $leadCategories = $this->getLeadCategoryRepository()->getLeadCategories($lead);

        $results = [];
        foreach ($categories as $category) {
            if (!isset($leadCategories[$category])) {
                $newLeadCategory = new LeadCategory();
                $newLeadCategory->setLead($lead);
                if (!$category instanceof Category) {
                    $category = $this->categoryModel->getEntity($category);
                }
                $newLeadCategory->setCategory($category);
                $newLeadCategory->setDateAdded(new \DateTime());
                $newLeadCategory->setManuallyAdded($manuallyAdded);
                $results[$category->getId()] = $newLeadCategory;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($lead, $category));
                }
            }
        }
        if (!empty($results)) {
            $this->getLeadCategoryRepository()->saveEntities($results);
        }

        return $results;
    }

    /**
     * @param $categories
     */
    public function removeFromCategories($categories)
    {
        $deleteCats = [];
        if (is_array($categories)) {
            foreach ($categories as $key => $category) {
                /** @var LeadCategory $category */
                $category     = $this->getLeadCategoryRepository()->getEntity($key);
                $deleteCats[] = $category;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($category->getLead(), $category->getCategory(), false));
                }
            }
        } elseif ($categories instanceof LeadCategory) {
            $deleteCats[] = $categories;

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($categories->getLead(), $categories->getCategory(), false));
            }
        }

        if (!empty($deleteCats)) {
            $this->getLeadCategoryRepository()->deleteEntities($deleteCats);
        }
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getLeadCategories(Lead $lead)
    {
        $leadCategories   = $this->getLeadCategoryRepository()->getLeadCategories($lead);
        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $leadCategoryList[$category['id']] = $category['category_id'];
        }

        return $leadCategoryList;
    }

    /**
     * @param array        $fields
     * @param array        $data
     * @param null         $owner
     * @param null         $list
     * @param null         $tags
     * @param bool         $persist
     * @param LeadEventLog $eventLog
     *
     * @return bool|null
     *
     * @throws \Exception
     *
     * @deprecated 2.10.0 To be removed in 3.0. Use `import` instead
     */
    public function importLead($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null)
    {
        return $this->import($fields, $data, $owner, $list, $tags, $persist, $eventLog);
    }

    /**
     * @param array        $fields
     * @param array        $data
     * @param null         $owner
     * @param null         $list
     * @param null         $tags
     * @param bool         $persist
     * @param LeadEventLog $eventLog
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function import($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null, $importId = null)
    {
        $fields    = array_flip($fields);
        $fieldData = [];

        // Extract company data and import separately
        // Modifies the data array
        $company                           = null;
        list($companyFields, $companyData) = $this->companyModel->extractCompanyDataFromImport($fields, $data);

        if (!empty($companyData)) {
            $companyFields = array_flip($companyFields);
            $this->companyModel->import($companyFields, $companyData, $owner, $list, $tags, $persist, $eventLog);
            $companyFields = array_flip($companyFields);

            $companyName    = isset($companyFields['companyname']) ? $companyData[$companyFields['companyname']] : null;
            $companyCity    = isset($companyFields['companycity']) ? $companyData[$companyFields['companycity']] : null;
            $companyCountry = isset($companyFields['companycountry']) ? $companyData[$companyFields['companycountry']] : null;
            $companyState   = isset($companyFields['companystate']) ? $companyData[$companyFields['companystate']] : null;

            $company = $this->companyModel->getRepository()->identifyCompany($companyName, $companyCity, $companyCountry, $companyState);
        }

        foreach ($fields as $leadField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data) && !is_null($data[$importField]) && $data[$importField] != '') {
                $fieldData[$leadField] = InputHelper::_($data[$importField], 'string');
            }
        }

        $lead   = $this->checkForDuplicateContact($fieldData);
        $merged = ($lead->getId());

        if (!empty($fields['dateAdded']) && !empty($data[$fields['dateAdded']])) {
            $dateAdded = new DateTimeHelper($data[$fields['dateAdded']]);
            $lead->setDateAdded($dateAdded->getUtcDateTime());
        }
        unset($fieldData['dateAdded']);

        if (!empty($fields['dateModified']) && !empty($data[$fields['dateModified']])) {
            $dateModified = new DateTimeHelper($data[$fields['dateModified']]);
            $lead->setDateModified($dateModified->getUtcDateTime());
        }
        unset($fieldData['dateModified']);

        if (!empty($fields['lastActive']) && !empty($data[$fields['lastActive']])) {
            $lastActive = new DateTimeHelper($data[$fields['lastActive']]);
            $lead->setLastActive($lastActive->getUtcDateTime());
        }
        unset($fieldData['lastActive']);

        if (!empty($fields['dateIdentified']) && !empty($data[$fields['dateIdentified']])) {
            $dateIdentified = new DateTimeHelper($data[$fields['dateIdentified']]);
            $lead->setDateIdentified($dateIdentified->getUtcDateTime());
        }
        unset($fieldData['dateIdentified']);

        if (!empty($fields['createdByUser']) && !empty($data[$fields['createdByUser']])) {
            $userRepo      = $this->em->getRepository('MauticUserBundle:User');
            $createdByUser = $userRepo->findByIdentifier($data[$fields['createdByUser']]);
            if ($createdByUser !== null) {
                $lead->setCreatedBy($createdByUser);
            }
        }
        unset($fieldData['createdByUser']);

        if (!empty($fields['modifiedByUser']) && !empty($data[$fields['modifiedByUser']])) {
            $userRepo       = $this->em->getRepository('MauticUserBundle:User');
            $modifiedByUser = $userRepo->findByIdentifier($data[$fields['modifiedByUser']]);
            if ($modifiedByUser !== null) {
                $lead->setModifiedBy($modifiedByUser);
            }
        }
        unset($fieldData['modifiedByUser']);

        if (!empty($fields['ip']) && !empty($data[$fields['ip']])) {
            $addresses = explode(',', $data[$fields['ip']]);
            foreach ($addresses as $address) {
                $address = trim($address);
                if (!$ipAddress = $this->ipAddressModel->findOneByIpAddress($address)) {
                    $ipAddress = new IpAddress();
                    $ipAddress->setIpAddress($address);
                }
                $lead->addIpAddress($ipAddress);
            }
        }
        unset($fieldData['ip']);

        if (!empty($fields['points']) && !empty($data[$fields['points']]) && $lead->getId() === null) {
            // Add points only for new leads
            $lead->setPoints($data[$fields['points']]);

            //add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($data[$fields['points']]);
            $log->setLead($lead);
            $log->setType('lead');
            $log->setEventName($this->translator->trans('mautic.lead.import.event.name'));
            $log->setActionName($this->translator->trans('mautic.lead.import.action.name', [
                '%name%' => $this->userHelper->getUser()->getUsername(),
            ]));
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);
        }

        if (!empty($fields['stage']) && !empty($data[$fields['stage']])) {
            static $stages = [];
            $stageName     = $data[$fields['stage']];
            if (!array_key_exists($stageName, $stages)) {
                // Set stage for contact
                $stage = $this->em->getRepository('MauticStageBundle:Stage')->getStageByName($stageName);

                if (empty($stage)) {
                    $stage = new Stage();
                    $stage->setName($stageName);
                    $stages[$stageName] = $stage;
                }
            } else {
                $stage = $stages[$stageName];
            }

            $lead->setStage($stage);

            //add a contact stage change log
            $log = new StagesChangeLog();
            $log->setStage($stage);
            $log->setEventName($stage->getId().':'.$stage->getName());
            $log->setLead($lead);
            $log->setActionName(
                $this->translator->trans(
                    'mautic.stage.import.action.name',
                    [
                        '%name%' => $this->userHelper->getUser()->getUsername(),
                    ]
                )
            );
            $log->setDateAdded(new \DateTime());
            $lead->stageChangeLog($log);
        }
        unset($fieldData['stage']);

        // Set unsubscribe status
        if (!empty($fields['doNotEmail']) && isset($data[$fields['doNotEmail']]) && (!empty($fields['email']) && !empty($data[$fields['email']]))) {
            $doNotEmail = filter_var($data[$fields['doNotEmail']], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $doNotEmail) {
                $reason = $this->translator->trans('mautic.lead.import.by.user', [
                    '%user%' => $this->userHelper->getUser()->getUsername(),
                ]);

                // The email must be set for successful unsubscribtion
                $lead->addUpdatedField('email', $data[$fields['email']]);
                if ($doNotEmail) {
                    $this->addDncForLead($lead, 'email', $reason, DNC::MANUAL);
                } else {
                    $this->removeDncForLead($lead, 'email', true);
                }
            }
        }

        unset($fieldData['doNotEmail']);

        if (!empty($fields['ownerusername']) && !empty($data[$fields['ownerusername']])) {
            try {
                $newOwner = $this->userProvider->loadUserByUsername($data[$fields['ownerusername']]);
                $lead->setOwner($newOwner);
                //reset default import owner if exists owner for contact
                $owner = null;
            } catch (NonUniqueResultException $exception) {
                // user not found
            }
        }
        unset($fieldData['ownerusername']);

        if ($owner !== null) {
            $lead->setOwner($this->em->getReference('MauticUserBundle:User', $owner));
        }

        if ($tags !== null) {
            $this->modifyTags($lead, $tags, null, false);
        }

        if (empty($this->leadFields)) {
            $this->leadFields = $this->leadFieldModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'f.isPublished',
                                'expr'   => 'eq',
                                'value'  => true,
                            ],
                            [
                                'column' => 'f.object',
                                'expr'   => 'eq',
                                'value'  => 'lead',
                            ],
                        ],
                    ],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                ]
            );
        }

        $fieldErrors = [];

        foreach ($this->leadFields as $leadField) {
            if (isset($fieldData[$leadField['alias']])) {
                if ('NULL' === $fieldData[$leadField['alias']]) {
                    $fieldData[$leadField['alias']] = null;

                    continue;
                }

                try {
                    $this->cleanFields($fieldData, $leadField);
                } catch (\Exception $exception) {
                    $fieldErrors[] = $leadField['alias'].': '.$exception->getMessage();
                }

                if ('email' === $leadField['type'] && !empty($fieldData[$leadField['alias']])) {
                    try {
                        $this->emailValidator->validate($fieldData[$leadField['alias']], false);
                    } catch (\Exception $exception) {
                        $fieldErrors[] = $leadField['alias'].': '.$exception->getMessage();
                    }
                }

                // Skip if the value is in the CSV row
                continue;
            } elseif ($lead->isNew() && $leadField['defaultValue']) {
                // Fill in the default value if any
                $fieldData[$leadField['alias']] = ('multiselect' === $leadField['type']) ? [$leadField['defaultValue']] : $leadField['defaultValue'];
            }
        }

        if ($fieldErrors) {
            $fieldErrors = implode("\n", $fieldErrors);

            throw new \Exception($fieldErrors);
        }

        // All clear
        foreach ($fieldData as $field => $value) {
            $lead->addUpdatedField($field, $value);
        }

        $lead->imported = true;

        if ($eventLog) {
            $action = $merged ? 'updated' : 'inserted';
            $eventLog->setAction($action);
        }

        if ($persist) {
            $lead->setManipulator(new LeadManipulator(
                'lead',
                'import',
                $importId,
                $this->userHelper->getUser()->getName()
            ));
            $this->saveEntity($lead);

            if ($list !== null) {
                $this->addToLists($lead, [$list]);
            }

            if ($company !== null) {
                $this->companyModel->addLeadToCompany($company, $lead);
            }

            if ($eventLog) {
                $lead->addEventLog($eventLog);
            }
        }

        return $merged;
    }

    /**
     * Update a leads tags.
     *
     * @param Lead       $lead
     * @param array      $tags
     * @param bool|false $removeOrphans
     */
    public function setTags(Lead $lead, array $tags, $removeOrphans = false)
    {
        /** @var Tag[] $currentTags */
        $currentTags  = $lead->getTags();
        $leadModified = $tagsDeleted = false;

        foreach ($currentTags as $tagName => $tag) {
            if (!in_array($tag->getId(), $tags)) {
                // Tag has been removed
                $lead->removeTag($tag);
                $leadModified = $tagsDeleted = true;
            } else {
                // Remove tag so that what's left are new tags
                $key = array_search($tag->getId(), $tags);
                unset($tags[$key]);
            }
        }

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                if (is_numeric($tag)) {
                    // Existing tag being added to this lead
                    $lead->addTag(
                        $this->em->getReference('MauticLeadBundle:Tag', $tag)
                    );
                } else {
                    $lead->addTag(
                        $this->getTagRepository()->getTagByNameOrCreateNewOne($tag)
                    );
                }
            }
            $leadModified = true;
        }

        if ($leadModified) {
            $this->saveEntity($lead);

            // Delete orphaned tags
            if ($tagsDeleted && $removeOrphans) {
                $this->getTagRepository()->deleteOrphans();
            }
        }
    }

    /**
     * Update a leads UTM tags.
     *
     * @param Lead   $lead
     * @param UtmTag $utmTags
     */
    public function setUtmTags(Lead $lead, UtmTag $utmTags)
    {
        $lead->setUtmTags($utmTags);

        $this->saveEntity($lead);
    }

    /**
     * Add leads UTM tags via API.
     *
     * @param Lead  $lead
     * @param array $params
     */
    public function addUTMTags(Lead $lead, $params)
    {
        // known "synonym" fields expected
        $synonyms = ['useragent'  => 'user_agent',
                     'remotehost' => 'remote_host', ];

        // convert 'query' option to an array if necessary
        if (isset($params['query']) && !is_array($params['query'])) {
            // assume it's a query string; convert it to array
            parse_str($params['query'], $queryResult);
            if (!empty($queryResult)) {
                $params['query'] = $queryResult;
            } else {
                // Something wrong with, remove it
                unset($params['query']);
            }
        }

        // Fix up known synonym/mismatch field names
        foreach ($synonyms as $expected => $replace) {
            if (key_exists($expected, $params) && !isset($params[$replace])) {
                // add expected key name
                $params[$replace] = $params[$expected];
            }
        }

        // see if active date set, so we can use it
        $updateLastActive = false;
        $lastActive       = new \DateTime();
        // should be: yyyy-mm-ddT00:00:00+00:00
        if (isset($params['lastActive'])) {
            $lastActive       = new \DateTime($params['lastActive']);
            $updateLastActive = true;
        }
        $params['date_added'] = $lastActive;

        // New utmTag
        $utmTags = new UtmTag();

        // get available fields and their setter.
        $fields = $utmTags->getFieldSetterList();

        // cycle through calling appropriate setter
        foreach ($fields as $q => $setter) {
            if (isset($params[$q])) {
                $utmTags->$setter($params[$q]);
            }
        }

        // create device
        if (!empty($params['useragent'])) {
            $this->deviceTracker->createDeviceFromUserAgent($lead, $params['useragent']);
        }

        // add the lead
        $utmTags->setLead($lead);
        if ($updateLastActive) {
            $lead->setLastActive($lastActive);
        }

        $this->setUtmTags($lead, $utmTags);
    }

    /**
     * Removes a UtmTag set from a Lead.
     *
     * @param Lead $lead
     * @param int  $utmId
     */
    public function removeUtmTags(Lead $lead, $utmId)
    {
        /** @var UtmTag $utmTag */
        foreach ($lead->getUtmTags() as $utmTag) {
            if ($utmTag->getId() === $utmId) {
                $lead->removeUtmTagEntry($utmTag);
                $this->saveEntity($lead);

                return true;
            }
        }

        return false;
    }

    /**
     * Modify tags with support to remove via a prefixed minus sign.
     *
     * @param Lead $lead
     * @param      $tags
     * @param      $removeTags
     * @param      $persist
     * @param bool True if tags modified
     *
     * @return bool
     */
    public function modifyTags(Lead $lead, $tags, array $removeTags = null, $persist = true)
    {
        $tagsModified = false;
        $leadTags     = $lead->getTags();

        if (!$leadTags->isEmpty()) {
            $this->logger->debug('CONTACT: Contact currently has tags '.implode(', ', $leadTags->getKeys()));
        } else {
            $this->logger->debug('CONTACT: Contact currently does not have any tags');
        }

        if (!is_array($tags)) {
            $tags = explode(',', $tags);
        }

        if (empty($tags) && empty($removeTags)) {
            return false;
        }

        $this->logger->debug('CONTACT: Adding '.implode(', ', $tags).' to contact ID# '.$lead->getId());

        array_walk($tags, function (&$val) {
            $val = trim($val);
            InputHelper::clean($val);
        });

        // See which tags already exist
        $foundTags = $this->getTagRepository()->getTagsByName($tags);
        foreach ($tags as $tag) {
            if (strpos($tag, '-') === 0) {
                // Tag to be removed
                $tag = substr($tag, 1);

                if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                    $tagsModified = true;
                    $lead->removeTag($foundTags[$tag]);

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            } else {
                $tagToBeAdded = null;

                if (!array_key_exists($tag, $foundTags)) {
                    $tagToBeAdded = new Tag($tag);
                } elseif (!$leadTags->contains($foundTags[$tag])) {
                    $tagToBeAdded = $foundTags[$tag];
                }

                if ($tagToBeAdded) {
                    $lead->addTag($tagToBeAdded);
                    $tagsModified = true;
                    $this->logger->debug('CONTACT: Added '.$tag);
                }
            }
        }

        if (!empty($removeTags)) {
            $this->logger->debug('CONTACT: Removing '.implode(', ', $removeTags).' for contact ID# '.$lead->getId());

            array_walk($removeTags, function (&$val) {
                $val = trim($val);
                InputHelper::clean($val);
            });

            // See which tags really exist
            $foundRemoveTags = $this->getTagRepository()->getTagsByName($removeTags);

            foreach ($removeTags as $tag) {
                // Tag to be removed
                if (array_key_exists($tag, $foundRemoveTags) && $leadTags->contains($foundRemoveTags[$tag])) {
                    $lead->removeTag($foundRemoveTags[$tag]);
                    $tagsModified = true;

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            }
        }

        if ($persist) {
            $this->saveEntity($lead);
        }

        return $tagsModified;
    }

    /**
     * Modify companies for lead.
     *
     * @param Lead $lead
     * @param $companies
     */
    public function modifyCompanies(Lead $lead, $companies)
    {
        // See which companies belong to the lead already
        $leadCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        foreach ($leadCompanies as $key => $leadCompany) {
            if (array_search($leadCompany['company_id'], $companies) === false) {
                $this->companyModel->removeLeadFromCompany([$leadCompany['company_id']], $lead);
            }
        }

        if (count($companies)) {
            $this->companyModel->addLeadToCompany($companies, $lead);
        } else {
            // update the lead's company name to nothing
            $lead->addUpdatedField('company', '');
            $this->getRepository()->saveEntity($lead);
        }
    }

    /**
     * Get array of available lead tags.
     */
    public function getTagList()
    {
        return $this->getTagRepository()->getSimpleList(null, [], 'tag', 'id');
    }

    /**
     * Get bar chart data of contacts.
     *
     * @param string    $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getLeadsLineChartData($unit, $dateFrom, $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $flag        = null;
        $topLists    = null;
        $allLeadsT   = $this->translator->trans('mautic.lead.all.leads');
        $identifiedT = $this->translator->trans('mautic.lead.identified');
        $anonymousT  = $this->translator->trans('mautic.lead.lead.anonymous');

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $chart                              = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query                              = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $anonymousFilter                    = $filter;
        $anonymousFilter['date_identified'] = [
            'expression' => 'isNull',
        ];
        $identifiedFilter                    = $filter;
        $identifiedFilter['date_identified'] = [
            'expression' => 'isNotNull',
        ];

        if ($flag == 'top') {
            $topLists = $this->leadListModel->getTopLists(6, $dateFrom, $dateTo);
            if ($topLists) {
                foreach ($topLists as $list) {
                    $filter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $all = $query->fetchTimeData('leads', 'date_added', $filter);
                    $chart->setDataset($list['name'].': '.$allLeadsT, $all);
                }
            }
        } elseif ($flag == 'topIdentifiedVsAnonymous') {
            $topLists = $this->leadListModel->getTopLists(3, $dateFrom, $dateTo);
            if ($topLists) {
                foreach ($topLists as $list) {
                    $anonymousFilter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $identifiedFilter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
                    $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
                    $chart->setDataset($list['name'].': '.$identifiedT, $identified);
                    $chart->setDataset($list['name'].': '.$anonymousT, $anonymous);
                }
            }
        } elseif ($flag == 'identified') {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $chart->setDataset($identifiedT, $identified);
        } elseif ($flag == 'anonymous') {
            $anonymous = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($anonymousT, $anonymous);
        } elseif ($flag == 'identifiedVsAnonymous') {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($identifiedT, $identified);
            $chart->setDataset($anonymousT, $anonymous);
        } else {
            $all = $query->fetchTimeData('leads', 'date_added', $filter);
            $chart->setDataset($allLeadsT, $all);
        }

        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getAnonymousVsIdentifiedPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $identified = $query->count('leads', 'date_identified', 'date_added', $filters);
        $all        = $query->count('leads', 'id', 'date_added', $filters);
        $chart->setDataset($this->translator->trans('mautic.lead.identified'), $identified);
        $chart->setDataset($this->translator->trans('mautic.lead.lead.anonymous'), ($all - $identified));

        return $chart->render();
    }

    /**
     * Get leads count per country name.
     * Can't use entity, because country is a custom field.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getLeadMapData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
    {
        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) as quantity, t.country')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->groupBy('t.country')
            ->where($q->expr()->isNotNull('t.country'));

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        $countries = array_flip(Intl::getRegionBundle()->getCountryNames('en'));
        $mapData   = [];

        // Convert country names to 2-char code
        if ($results) {
            foreach ($results as $leadCountry) {
                if (isset($countries[$leadCountry['country']])) {
                    $mapData[$countries[$leadCountry['country']]] = $leadCountry['quantity'];
                }
            }
        }

        return $mapData;
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopOwners($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.owner_id, u.first_name, u.last_name')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = t.owner_id')
            ->where($q->expr()->isNotNull('t.owner_id'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.owner_id, u.first_name, u.last_name')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopCreators($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.created_by, t.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->where($q->expr()->isNotNull('t.created_by'))
            ->andWhere($q->expr()->isNotNull('t.created_by_user'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.created_by, t.created_by_user')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of leads in a date range.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param array     $options
     *
     * @return array
     */
    public function getLeadList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        if (!empty($options['canViewOthers'])) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.firstname, t.lastname, t.email, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        if (empty($options['includeAnonymous'])) {
            $q->andWhere($q->expr()->isNotNull('t.date_identified'));
        }
        $results = $q->execute()->fetchAll();

        if ($results) {
            foreach ($results as &$result) {
                if ($result['firstname'] || $result['lastname']) {
                    $result['name'] = trim($result['firstname'].' '.$result['lastname']);
                } elseif ($result['email']) {
                    $result['name'] = $result['email'];
                } else {
                    $result['name'] = 'anonymous';
                }
                unset($result['firstname']);
                unset($result['lastname']);
                unset($result['email']);
            }
        }

        return $results;
    }

    /**
     * Get timeline/engagement data.
     *
     * @param Lead|null  $lead
     * @param null       $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     * @param bool       $forTimeline
     *
     * @return array
     */
    public function getEngagements(Lead $lead = null, $filters = null, array $orderBy = null, $page = 1, $limit = 25, $forTimeline = true)
    {
        $event = $this->dispatcher->dispatch(
            LeadEvents::TIMELINE_ON_GENERATE,
            new LeadTimelineEvent($lead, $filters, $orderBy, $page, $limit, $forTimeline, $this->coreParametersHelper->getParameter('site_url'))
        );

        $payload = [
            'events'   => $event->getEvents(),
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $event->getEventTypes(),
            'total'    => $event->getEventCounter()['total'],
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => $event->getMaxPage(),
        ];

        return ($forTimeline) ? $payload : [$payload, $event->getSerializerGroups()];
    }

    /**
     * @return array
     */
    public function getEngagementTypes()
    {
        $event = new LeadTimelineEvent();
        $event->fetchTypesOnly();

        $this->dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $event);

        return $event->getEventTypes();
    }

    /**
     * Get engagement counts by time unit.
     *
     * @param Lead            $lead
     * @param \DateTime|null  $dateFrom
     * @param \DateTime|null  $dateTo
     * @param string          $unit
     * @param ChartQuery|null $chartQuery
     *
     * @return array
     */
    public function getEngagementCount(Lead $lead, \DateTime $dateFrom = null, \DateTime $dateTo = null, $unit = 'm', ChartQuery $chartQuery = null)
    {
        $event = new LeadTimelineEvent($lead);
        $event->setCountOnly($dateFrom, $dateTo, $unit, $chartQuery);

        $this->dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $event);

        return $event->getEventCounter();
    }

    /**
     * @param Lead $lead
     * @param      $company
     *
     * @return bool
     */
    public function addToCompany(Lead $lead, $company)
    {
        //check if lead is in company already
        if (!$company instanceof Company) {
            $company = $this->companyModel->getEntity($company);
        }

        // company does not exist anymore
        if ($company === null) {
            return false;
        }

        $companyLead = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId(), $company->getId());

        if (empty($companyLead)) {
            $this->companyModel->addLeadToCompany($company, $lead);

            return true;
        }

        return false;
    }

    /**
     * Get contact channels.
     *
     * @param Lead $lead
     *
     * @return array
     */
    public function getContactChannels(Lead $lead)
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if ($this->isContactable($lead, $channel) === DNC::IS_CONTACTABLE) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Get contact channels.
     *
     * @param Lead $lead
     *
     * @return array
     */
    public function getDoNotContactChannels(Lead $lead)
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if ($this->isContactable($lead, $channel) !== DNC::IS_CONTACTABLE) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    /**
     * @deprecatd 2.4; to be removed in 3.0
     * use mautic.channel.helper.channel_list service (Mautic\ChannelBundle\Helper\ChannelListHelper) to obtain the desired channels
     *
     * Get contact channels.
     *
     * @return array
     */
    public function getAllChannels()
    {
        return $this->channelListHelper->getChannelList();
    }

    /**
     * @return array
     */
    public function getPreferenceChannels()
    {
        return $this->channelListHelper->getFeatureChannels(self::CHANNEL_FEATURE, true);
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getPreferredChannel(Lead $lead)
    {
        $preferredChannel = $this->getFrequencyRuleRepository()->getPreferredChannel($lead->getId());
        if (!empty($preferredChannel)) {
            return $preferredChannel[0];
        }

        return [];
    }

    /**
     * @param $companyId
     * @param $leadId
     *
     * @return array
     */
    public function setPrimaryCompany($companyId, $leadId)
    {
        $companyArray      = [];
        $oldPrimaryCompany = $newPrimaryCompany = false;

        $lead = $this->getEntity($leadId);

        $companyLeads = $this->companyModel->getCompanyLeadRepository()->getEntitiesByLead($lead);

        /** @var CompanyLead $companyLead */
        foreach ($companyLeads as $companyLead) {
            $company = $companyLead->getCompany();

            if ($companyLead) {
                if ($companyLead->getPrimary() && !$oldPrimaryCompany) {
                    $oldPrimaryCompany = $companyLead->getCompany()->getId();
                }
                if ($company->getId() === (int) $companyId) {
                    $companyLead->setPrimary(true);
                    $newPrimaryCompany = $companyId;
                    $lead->addUpdatedField('company', $company->getName());
                } else {
                    $companyLead->setPrimary(false);
                }
                $companyArray[] = $companyLead;
            }
        }

        if (!$newPrimaryCompany) {
            $latestCompany = $this->companyModel->getCompanyLeadRepository()->getLatestCompanyForLead($leadId);
            if (!empty($latestCompany)) {
                $lead->addUpdatedField('company', $latestCompany['companyname'])
                    ->setDateModified(new \DateTime());
            }
        }

        if (!empty($companyArray)) {
            $this->em->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            $this->companyModel->getCompanyLeadRepository()->saveEntities($companyArray, false);
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->em->clear(CompanyLead::class);

        return ['oldPrimary' => $oldPrimaryCompany, 'newPrimary' => $companyId];
    }

    /**
     * @param Lead $lead
     * @param $score
     *
     * @return bool
     */
    public function scoreContactsCompany(Lead $lead, $score)
    {
        $success          = false;
        $entities         = [];
        $contactCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        if (!empty($contactCompanies)) {
            foreach ($contactCompanies as $contactCompany) {
                $company  = $this->companyModel->getEntity($contactCompany['company_id']);
                $oldScore = $company->getScore();
                $newScore = $score + $oldScore;
                $company->setScore($newScore);
                $entities[] = $company;
                $success    = true;
            }
        }

        if (!empty($entities)) {
            $this->companyModel->getRepository()->saveEntities($entities);
        }

        return $success;
    }

    /**
     * @param Lead $lead
     * @param      $ownerId
     */
    public function updateLeadOwner(Lead $lead, $ownerId)
    {
        $owner = $this->em->getReference(User::class, $ownerId);
        $lead->setOwner($owner);

        parent::saveEntity($lead);
    }

    /**
     * @param Lead $lead
     */
    private function processManipulator(Lead $lead)
    {
        if ($lead->isNewlyCreated() || $lead->wasAnonymous()) {
            // Only store an entry once for created and once for identified, not every time the lead is saved
            $manipulator = $lead->getManipulator();
            if ($manipulator !== null) {
                $manipulationLog = new LeadEventLog();
                $manipulationLog->setLead($lead)
                    ->setBundle($manipulator->getBundleName())
                    ->setObject($manipulator->getObjectName())
                    ->setObjectId($manipulator->getObjectId());
                if ($lead->isAnonymous()) {
                    $manipulationLog->setAction('created_contact');
                } else {
                    $manipulationLog->setAction('identified_contact');
                }
                $description = $manipulator->getObjectDescription();
                $manipulationLog->setProperties(['object_description' => $description]);

                $lead->addEventLog($manipulationLog);
                $lead->setManipulator(null);
            }
        }
    }

    /**
     * @param IpAddress $ip
     * @param bool      $persist
     *
     * @return Lead
     */
    protected function createNewContact(IpAddress $ip, $persist = true)
    {
        //let's create a lead
        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setNewlyCreated(true);

        if ($persist && !defined('MAUTIC_NON_TRACKABLE_REQUEST')) {
            // Set to prevent loops
            $this->contactTracker->setTrackedContact($lead);

            // Note ignoring a lead manipulator object here on purpose to not falsely record entries
            $this->saveEntity($lead, false);

            $fields = $this->getLeadDetails($lead);
            $lead->setFields($fields);
        }

        if ($leadId = $lead->getId()) {
            $this->logger->addDebug("LEAD: New lead created with ID# $leadId.");
        }

        return $lead;
    }

    /**
     * @deprecated 2.12.0 to be removed in 3.0; use Mautic\LeadBundle\Model\DoNotContact instead
     *
     * @param Lead   $lead
     * @param string $channel
     *
     * @return int
     *
     * @see \Mautic\LeadBundle\Entity\DoNotContact This method can return boolean false, so be
     *                                             sure to always compare the return value against
     *                                             the class constants of DoNotContact
     */
    public function isContactable(Lead $lead, $channel)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository('MauticLeadBundle:DoNotContact');

        /** @var \Mautic\LeadBundle\Entity\DoNotContact[] $entries */
        $dncEntries = $dncRepo->getEntriesByLeadAndChannel($lead, $channel);

        // If the lead has no entries in the DNC table, we're good to go
        if (empty($dncEntries)) {
            return DNC::IS_CONTACTABLE;
        }

        foreach ($dncEntries as $dnc) {
            if ($dnc->getReason() !== DNC::IS_CONTACTABLE) {
                return $dnc->getReason();
            }
        }

        return DNC::IS_CONTACTABLE;
    }

    /**
     * Remove a Lead's DNC entry based on channel.
     *
     * @deprecated 2.12.0 to be removed in 3.0; use Mautic\LeadBundle\Model\DoNotContact instead
     *
     * @param Lead      $lead
     * @param string    $channel
     * @param bool|true $persist
     *
     * @return bool
     */
    public function removeDncForLead(Lead $lead, $channel, $persist = true)
    {
        /** @var DNC $dnc */
        foreach ($lead->getDoNotContact() as $dnc) {
            if ($dnc->getChannel() === $channel) {
                $lead->removeDoNotContactEntry($dnc);

                if ($persist) {
                    $this->saveEntity($lead);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Create a DNC entry for a lead.
     *
     * @deprecated 2.12.0 to be removed in 3.0; use Mautic\LeadBundle\Model\DoNotContact instead
     *
     * @param Lead         $lead
     * @param string|array $channel            If an array with an ID, use the structure ['email' => 123]
     * @param string       $comments
     * @param int          $reason             Must be a class constant from the DoNotContact class
     * @param bool         $persist
     * @param bool         $checkCurrentStatus
     * @param bool         $override
     *
     * @return bool|DNC If a DNC entry is added or updated, returns the DNC object. If a DNC is already present
     *                  and has the specified reason, nothing is done and this returns false
     */
    public function addDncForLead(Lead $lead, $channel, $comments = '', $reason = DNC::BOUNCED, $persist = true, $checkCurrentStatus = true, $override = false)
    {
        // if !$checkCurrentStatus, assume is contactable due to already being valided
        $isContactable = ($checkCurrentStatus) ? $this->isContactable($lead, $channel) : DNC::IS_CONTACTABLE;

        // If they don't have a DNC entry yet
        if ($isContactable === DNC::IS_CONTACTABLE) {
            $dnc = new DNC();

            if (is_array($channel)) {
                $channelId = reset($channel);
                $channel   = key($channel);

                $dnc->setChannelId((int) $channelId);
            }

            $dnc->setChannel($channel);
            $dnc->setReason($reason);
            $dnc->setLead($lead);
            $dnc->setDateAdded(new \DateTime());
            $dnc->setComments($comments);

            $lead->addDoNotContactEntry($dnc);

            if ($persist) {
                // Use model saveEntity to trigger events for DNC change
                $this->saveEntity($lead);
            }

            return $dnc;
        }
        // Or if the given reason is different than the stated reason
        elseif ($isContactable !== $reason) {
            /** @var DNC $dnc */
            foreach ($lead->getDoNotContact() as $dnc) {
                // Only update if the contact did not unsubscribe themselves
                if (!$override && $dnc->getReason() !== DNC::UNSUBSCRIBED) {
                    $override = true;
                }
                if ($dnc->getChannel() === $channel && $override) {
                    // Remove the outdated entry
                    $lead->removeDoNotContactEntry($dnc);

                    // Update the DNC entry
                    $dnc->setChannel($channel);
                    $dnc->setReason($reason);
                    $dnc->setLead($lead);
                    $dnc->setDateAdded(new \DateTime());
                    $dnc->setComments($comments);

                    // Re-add the entry to the lead
                    $lead->addDoNotContactEntry($dnc);

                    if ($persist) {
                        // Use model saveEntity to trigger events for DNC change
                        $this->saveEntity($lead);
                    }

                    return $dnc;
                }
            }
        }

        return false;
    }

    /**
     * @param bool $forceRegeneration
     *
     * @deprecated 2.13.0 to be removed in 3.0; use the DeviceTrackingService
     */
    public function getTrackingCookie($forceRegeneration = false)
    {
        @trigger_error('getTrackingCookie is deprecated and will be removed in 3.0; Use the ContactTracker::getTrackingId instead', E_USER_DEPRECATED);

        return [$this->contactTracker->getTrackingId(), false];
    }

    /**
     * @param $leadId
     *
     * @deprecated 2.13.0 to be removed in 3.0
     */
    public function setLeadCookie($leadId)
    {
        // No longer used
    }

    /**
     * Get the current lead; if $returnTracking = true then array with lead, trackingId, and boolean of if trackingId
     * was just generated or not.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param bool|false $returnTracking
     *
     * @return null|Lead|array
     */
    public function getCurrentLead($returnTracking = false)
    {
        @trigger_error('getCurrentLead is deprecated and will be removed in 3.0; Use the ContactTracker::getContact instead', E_USER_DEPRECATED);

        $trackedContact = $this->contactTracker->getContact();
        $trackingId     = $this->contactTracker->getTrackingId();

        return ($returnTracking) ? [$trackedContact, $trackingId, false] : $trackedContact;
    }

    /**
     * Sets current lead.
     *
     * @deprecated 2.13.0 to be removed in 3.0; use ContactTracker::getContact instead
     *
     * @param Lead $lead
     */
    public function setCurrentLead(Lead $lead)
    {
        @trigger_error('setCurrentLead is deprecated and will be removed in 3.0; Use the ContactTracker::setTrackedContact instead', E_USER_DEPRECATED);

        $this->contactTracker->setTrackedContact($lead);
    }

    /**
     * Used by system processes that hook into events that use getCurrentLead().
     *
     * @param Lead $lead
     */
    public function setSystemCurrentLead(Lead $lead = null)
    {
        @trigger_error('setSystemCurrentLead is deprecated and will be removed in 3.0; Use the ContactTracker::setSystemContac instead', E_USER_DEPRECATED);

        $this->contactTracker->setSystemContact($lead);
    }

    /**
     * Merge two leads; if a conflict of data occurs, the newest lead will get precedence.
     *
     * @deprecated 2.13.0; to be removed in 3.0. Use \Mautic\LeadBundle\Deduplicate\ContactMerger instead
     *
     * @param Lead $lead
     * @param Lead $lead2
     * @param bool $autoMode If true, the newest lead will be merged into the oldes then deleted; otherwise, $lead will be merged into $lead2 then deleted
     *
     * @return Lead
     */
    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true)
    {
        return $this->legacyLeadModel->mergeLeads($lead, $lead2, $autoMode);
    }
}
