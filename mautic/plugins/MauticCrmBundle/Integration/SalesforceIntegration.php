<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember\Fetcher;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember\Organizer;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Exception\NoObjectsToFetchException;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Helper\StateValidationHelper;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\CampaignMember;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\ResultsPaginator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class SalesforceIntegration.
 *
 * @method SalesforceApi getApiHelper
 */
class SalesforceIntegration extends CrmAbstractIntegration
{
    private $objects = [
        'Lead',
        'Contact',
        'Account',
    ];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Salesforce';
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.consumerid',
            'client_secret' => 'mautic.integration.keyfield.consumersecret',
        ];
    }

    /**
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return ['refresh_token', ''];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        $config = $this->mergeConfigToFeatureSettings([]);

        if (isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox') {
            return 'https://test.salesforce.com/services/oauth2/token';
        }

        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        $config = $this->mergeConfigToFeatureSettings([]);

        if (isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox') {
            return 'https://test.salesforce.com/services/oauth2/authorize';
        }

        return 'https://login.salesforce.com/services/oauth2/authorize';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'api refresh_token';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return sprintf('%s/services/data/v34.0/sobjects', $this->keys['instance_url']);
    }

    /**
     * @return string
     */
    public function getQueryUrl()
    {
        return sprintf('%s/services/data/v34.0', $this->keys['instance_url']);
    }

    /**
     * @return string
     */
    public function getCompositeUrl()
    {
        return sprintf('%s/services/data/v38.0', $this->keys['instance_url']);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && isset($this->keys[$this->getAuthTokenKey()])) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function getDataPriority()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function updateDncByDate()
    {
        $featureSettings = $this->settings->getFeatureSettings();
        if (isset($featureSettings['updateDncByDate'][0]) && $featureSettings['updateDncByDate'][0] === 'updateDncByDate') {
            return true;
        }

        return false;
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getFormLeadFields($settings = [])
    {
        $leadFields    = $this->getFormFieldsByObject('Lead', $settings);
        $contactFields = $this->getFormFieldsByObject('Contact', $settings);

        return array_merge($leadFields, $contactFields);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $salesForceObjects = [];

        if (isset($settings['feature_settings']['objects'])) {
            $salesForceObjects = $settings['feature_settings']['objects'];
        } else {
            $salesForceObjects[] = 'Lead';
        }

        $isRequired = function (array $field, $object) {
            return
                ($field['type'] !== 'boolean' && empty($field['nillable']) && !in_array($field['name'], ['Status', 'Id', 'CreatedDate'])) ||
                ($object == 'Lead' && in_array($field['name'], ['Company'])) ||
                (in_array($object, ['Lead', 'Contact']) && 'Email' === $field['name']);
        };

        $salesFields = [];
        try {
            if (!empty($salesForceObjects) and is_array($salesForceObjects)) {
                foreach ($salesForceObjects as $key => $sfObject) {
                    if ('Account' === $sfObject) {
                        // Match SF object to Mautic's
                        $sfObject = 'company';
                    }

                    if (isset($sfObject) and $sfObject == 'Activity') {
                        continue;
                    }

                    $sfObject = trim($sfObject);
                    // Check the cache first
                    $settings['cache_suffix'] = $cacheSuffix = '.'.$sfObject;
                    if ($fields = parent::getAvailableLeadFields($settings)) {
                        if (('company' === $sfObject && isset($fields['Id'])) || isset($fields['Id__'.$sfObject])) {
                            $salesFields[$sfObject] = $fields;

                            continue;
                        }
                    }

                    if ($this->isAuthorized()) {
                        if (!isset($salesFields[$sfObject])) {
                            $fields = $this->getApiHelper()->getLeadFields($sfObject);
                            if (!empty($fields['fields'])) {
                                foreach ($fields['fields'] as $fieldInfo) {
                                    if ((!$fieldInfo['updateable'] && (!$fieldInfo['calculated'] && !in_array($fieldInfo['name'], ['Id', 'IsDeleted', 'CreatedDate'])))
                                        || !isset($fieldInfo['name'])
                                        || (in_array(
                                                $fieldInfo['type'],
                                                ['reference']
                                            ) && $fieldInfo['name'] != 'AccountId')
                                    ) {
                                        continue;
                                    }
                                    switch ($fieldInfo['type']) {
                                        case 'boolean': $type = 'boolean';
                                            break;
                                        case 'datetime': $type = 'datetime';
                                            break;
                                        case 'date': $type = 'date';
                                            break;
                                        default: $type = 'string';
                                    }
                                    if ($sfObject !== 'company') {
                                        if ($fieldInfo['name'] == 'AccountId') {
                                            $fieldInfo['label'] = 'Company';
                                        }
                                        $salesFields[$sfObject][$fieldInfo['name'].'__'.$sfObject] = [
                                            'type'        => $type,
                                            'label'       => $sfObject.'-'.$fieldInfo['label'],
                                            'required'    => $isRequired($fieldInfo, $sfObject),
                                            'group'       => $sfObject,
                                            'optionLabel' => $fieldInfo['label'],
                                        ];

                                        // CreateDate can be updatable just in Mautic
                                        if (in_array($fieldInfo['name'], ['CreatedDate'])) {
                                            $salesFields[$sfObject][$fieldInfo['name'].'__'.$sfObject]['update_mautic'] = 1;
                                        }
                                    } else {
                                        $salesFields[$sfObject][$fieldInfo['name']] = [
                                            'type'     => $type,
                                            'label'    => $fieldInfo['label'],
                                            'required' => $isRequired($fieldInfo, $sfObject),
                                        ];
                                    }
                                }

                                $this->cache->set('leadFields'.$cacheSuffix, $salesFields[$sfObject]);
                            }
                        }

                        asort($salesFields[$sfObject]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $salesFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return array
     */
    public function getFormNotes($section)
    {
        if ($section == 'authorization') {
            return ['mautic.salesforce.form.oauth_requirements', 'warning'];
        }

        return parent::getFormNotes($section);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function getFetchQuery($params)
    {
        $dateRange = $params;

        return $dateRange;
    }

    /**
     * @param       $data
     * @param       $object
     * @param array $params
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object, $params = [])
    {
        $updated               = 0;
        $created               = 0;
        $counter               = 0;
        $entity                = null;
        $detachClass           = null;
        $mauticObjectReference = null;
        $integrationMapping    = [];

        if (isset($data['records']) and $object !== 'Activity') {
            foreach ($data['records'] as $record) {
                $this->logger->debug('SALESFORCE: amendLeadDataBeforeMauticPopulate record '.var_export($record, true));
                if (isset($params['progress'])) {
                    $params['progress']->advance();
                }

                $dataObject = [];
                if (isset($record['attributes']['type']) && $record['attributes']['type'] == 'Account') {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }

                foreach ($record as $key => $item) {
                    if (is_bool($item)) {
                        $dataObject[$key.$newName] = (int) $item;
                    } else {
                        $dataObject[$key.$newName] = $item;
                    }
                }

                if (isset($dataObject) && $dataObject) {
                    $entity = false;
                    switch ($object) {
                        case 'Contact':
                            if (isset($dataObject['Email__Contact'])) {
                                // Sanitize email to make sure we match it
                                // correctly against mautic emails
                                $dataObject['Email__Contact'] = InputHelper::email($dataObject['Email__Contact']);
                            }

                            //get company from account id and assign company name
                            if (isset($dataObject['AccountId__'.$object])) {
                                $companyName = $this->getCompanyName($dataObject['AccountId__'.$object], 'Name');

                                if ($companyName) {
                                    $dataObject['AccountId__'.$object] = $companyName;
                                } else {
                                    unset($dataObject['AccountId__'.$object]); //no company was found in Salesforce
                                }
                            }
                        case 'Lead':
                            // Set owner so that it maps if configured to do so
                            if (!empty($dataObject['Owner__Lead']['Email'])) {
                                $dataObject['owner_email'] = $dataObject['Owner__Lead']['Email'];
                            } elseif (!empty($dataObject['Owner__Contact']['Email'])) {
                                $dataObject['owner_email'] = $dataObject['Owner__Contact']['Email'];
                            }

                            if (isset($dataObject['Email__Lead'])) {
                                // Sanitize email to make sure we match it
                                // correctly against mautic_leads emails
                                $dataObject['Email__Lead'] = InputHelper::email($dataObject['Email__Lead']);
                            }

                            $entity                = $this->getMauticLead($dataObject, true, null, null, $object);
                            $mauticObjectReference = 'lead';
                            $detachClass           = Lead::class;

                            break;
                        case 'Account':
                            $entity                = $this->getMauticCompany($dataObject, 'Account');
                            $mauticObjectReference = 'company';
                            $detachClass           = Company::class;

                            break;
                        default:
                            $this->logIntegrationError(
                                new \Exception(
                                    sprintf('Received an unexpected object without an internalObjectReference "%s"', $object)
                                )
                            );
                            break;
                    }

                    if (!$entity) {
                        continue;
                    }

                    $integrationMapping[$entity->getId()] = [
                        'entity'                => $entity,
                        'integration_entity_id' => $record['Id'],
                    ];

                    if (method_exists($entity, 'isNewlyCreated') && $entity->isNewlyCreated()) {
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    ++$counter;

                    if ($counter >= 100) {
                        // Persist integration entities
                        $this->buildIntegrationEntities($integrationMapping, $object, $mauticObjectReference, $params);
                        $counter = 0;
                        $this->em->clear($detachClass);
                        $integrationMapping = [];
                    }
                }
            }

            if (count($integrationMapping)) {
                // Persist integration entities
                $this->buildIntegrationEntities($integrationMapping, $object, $mauticObjectReference, $params);
                $this->em->clear($detachClass);
            }

            unset($data['records']);
            $this->logger->debug('SALESFORCE: amendLeadDataBeforeMauticPopulate response '.var_export($data, true));

            unset($data);
            $this->persistIntegrationEntities = [];
            unset($dataObject);
        }

        return [$updated, $created];
    }

    /**
     * @param FormBuilder $builder
     * @param array       $data
     * @param string      $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add(
                'sandbox',
                'choice',
                [
                    'choices' => [
                        'sandbox' => 'mautic.salesforce.sandbox',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.sandbox',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateOwner',
                'choice',
                [
                    'choices' => [
                        'updateOwner' => 'mautic.salesforce.updateOwner',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.updateOwner',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );
            $builder->add(
                'updateBlanks',
                'choice',
                [
                    'choices' => [
                        'updateBlanks' => 'mautic.integrations.blanks',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integrations.form.blanks',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
            $builder->add(
                'updateDncByDate',
                'choice',
                [
                    'choices' => [
                        'updateDncByDate' => 'mautic.integrations.update.dnc.by.date',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integrations.form.update.dnc.by.date.label',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'Lead'     => 'mautic.salesforce.object.lead',
                        'Contact'  => 'mautic.salesforce.object.contact',
                        'company'  => 'mautic.salesforce.object.company',
                        'Activity' => 'mautic.salesforce.object.activity',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'activityEvents',
                ChoiceType::class,
                [
                    'choices'    => $this->leadModel->getEngagementTypes(),
                    'label'      => 'mautic.salesforce.form.activity_included_events',
                    'label_attr' => [
                        'class'       => 'control-label',
                        'data-toggle' => 'tooltip',
                        'title'       => $this->translator->trans('mautic.salesforce.form.activity.events.tooltip'),
                    ],
                    'multiple'   => true,
                    'empty_data' => ['point.gained', 'form.submitted', 'email.read'], // BC with pre 2.11.0
                    'required'   => false,
                ]
            );

            $builder->add(
                'namespace',
                'text',
                [
                    'label'      => 'mautic.salesforce.form.namespace_prefix',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        }
    }

    /**
     * @param array $fields
     * @param array $keys
     * @param mixed $object
     *
     * @return array
     */
    public function prepareFieldsForSync($fields, $keys, $object = null)
    {
        $leadFields = [];
        if (null === $object) {
            $object = 'Lead';
        }

        $objects = (!is_array($object)) ? [$object] : $object;
        if (is_string($object) && 'Account' === $object) {
            return isset($fields['companyFields']) ? $fields['companyFields'] : $fields;
        }

        if (isset($fields['leadFields'])) {
            $fields = $fields['leadFields'];
            $keys   = array_keys($fields);
        }

        foreach ($objects as $obj) {
            if (!isset($leadFields[$obj])) {
                $leadFields[$obj] = [];
            }

            foreach ($keys as $key) {
                if (strpos($key, '__'.$obj)) {
                    $newKey = str_replace('__'.$obj, '', $key);
                    if ('Id' === $newKey) {
                        // Don't map Id for push
                        continue;
                    }

                    $leadFields[$obj][$newKey] = $fields[$key];
                }
            }
        }

        return (is_array($object)) ? $leadFields : $leadFields[$object];
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->mapContactDataForPush($lead, $config);

        // No fields are mapped so bail
        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $existingPersons = $this->getApiHelper()->getPerson(
                    [
                        'Lead'    => isset($mappedData['Lead']['create']) ? $mappedData['Lead']['create'] : null,
                        'Contact' => isset($mappedData['Contact']['create']) ? $mappedData['Contact']['create'] : null,
                    ]
                );

                $personFound = false;
                $people      = [
                    'Contact' => [],
                    'Lead'    => [],
                ];

                foreach (['Contact', 'Lead'] as $object) {
                    if (!empty($existingPersons[$object])) {
                        $fieldsToUpdate = $mappedData[$object]['update'];
                        $fieldsToUpdate = $this->getBlankFieldsToUpdate($fieldsToUpdate, $existingPersons[$object], $mappedData, $config);
                        $personFound    = true;
                        foreach ($existingPersons[$object] as $person) {
                            if (!empty($fieldsToUpdate)) {
                                if (isset($fieldsToUpdate['AccountId'])) {
                                    $accountId = $this->getCompanyName($fieldsToUpdate['AccountId'], 'Id', 'Name');
                                    if (!$accountId) {
                                        //company was not found so create a new company in Salesforce
                                        $company = $lead->getPrimaryCompany();
                                        if (!empty($company)) {
                                            $company   = $this->companyModel->getEntity($company['id']);
                                            $sfCompany = $this->pushCompany($company);
                                            if ($sfCompany) {
                                                $fieldsToUpdate['AccountId'] = key($sfCompany);
                                            }
                                        }
                                    } else {
                                        $fieldsToUpdate['AccountId'] = $accountId;
                                    }
                                }

                                $personData = $this->getApiHelper()->updateObject($fieldsToUpdate, $object, $person['Id']);
                            }

                            $people[$object][$person['Id']] = $person['Id'];
                        }
                    }

                    if ('Lead' === $object && !$personFound && isset($mappedData[$object]['create'])) {
                        $personData                         = $this->getApiHelper()->createLead($mappedData[$object]['create']);
                        $people[$object][$personData['Id']] = $personData['Id'];
                        $personFound                        = true;
                    }

                    if (isset($personData['Id'])) {
                        /** @var IntegrationEntityRepository $integrationEntityRepo */
                        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                        $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'lead', $lead->getId());

                        $integrationEntity = (empty($integrationId))
                            ? $this->createIntegrationEntity($object, $personData['Id'], 'lead', $lead->getId(), [], false)
                            :
                            $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationId[0]['id']);

                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                        $integrationEntityRepo->saveEntity($integrationEntity);
                    }
                }

                // Return success if any Contact or Lead was updated or created
                return ($personFound) ? $people : false;
            }
        } catch (\Exception $e) {
            if ($e instanceof ApiErrorException) {
                $e->setContact($lead);
            }

            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Company $company
     * @param array                             $config
     *
     * @return array|bool
     */
    public function pushCompany($company, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['companyFields']) || !$company) {
            return [];
        }
        $object     = 'company';
        $mappedData = $this->mapCompanyDataForPush($company, $config);

        // No fields are mapped so bail
        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $existingCompanies = $this->getApiHelper()->getCompany(
                    [
                        $object => $mappedData[$object]['create'],
                    ]
                );
                $companyFound = false;
                $companies    = [];

                if (!empty($existingCompanies[$object])) {
                    $fieldsToUpdate = $mappedData[$object]['update'];

                    $fieldsToUpdate = $this->getBlankFieldsToUpdate($fieldsToUpdate, $existingCompanies[$object], $mappedData, $config);
                    $companyFound   = true;

                    foreach ($existingCompanies[$object] as $sfCompany) {
                        if (!empty($fieldsToUpdate)) {
                            $companyData = $this->getApiHelper()->updateObject($fieldsToUpdate, $object, $sfCompany['Id']);
                        }
                        $companies[$sfCompany['Id']] = $sfCompany['Id'];
                    }
                }

                if (!$companyFound) {
                    $companyData                   = $this->getApiHelper()->createObject($mappedData[$object]['create'], 'Account');
                    $companies[$companyData['Id']] = $companyData['Id'];
                    $companyFound                  = true;
                }

                if (isset($companyData['Id'])) {
                    /** @var IntegrationEntityRepository $integrationEntityRepo */
                    $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                    $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'company', $company->getId());

                    $integrationEntity = (empty($integrationId))
                        ? $this->createIntegrationEntity($object, $companyData['Id'], 'lead', $company->getId(), [], false)
                        :
                        $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationId[0]['id']);

                    $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                    $integrationEntityRepo->saveEntity($integrationEntity);
                }

                // Return success if any company was updated or created
                return ($companyFound) ? $companies : false;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param array  $params
     * @param null   $query
     * @param null   $executed
     * @param array  $result
     * @param string $object
     *
     * @return array|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Lead')
    {
        if (!$query) {
            $query = $this->getFetchQuery($params);
        }

        if (!is_array($executed)) {
            $executed = [
                0 => 0,
                1 => 0,
            ];
        }

        try {
            if ($this->isAuthorized()) {
                $progress  = null;
                $paginator = new ResultsPaginator($this->logger, $this->keys['instance_url']);

                while (true) {
                    $result = $this->getApiHelper()->getLeads($query, $object);
                    $paginator->setResults($result);

                    if (isset($params['output']) && !isset($params['progress'])) {
                        $progress = new ProgressBar($params['output'], $paginator->getTotal());
                        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% ('.$object.')');

                        $params['progress'] = $progress;
                    }

                    list($justUpdated, $justCreated) = $this->amendLeadDataBeforeMauticPopulate($result, $object, $params);

                    $executed[0] += $justUpdated;
                    $executed[1] += $justCreated;

                    if (!$nextUrl = $paginator->getNextResultsUrl()) {
                        // No more records to fetch
                        break;
                    }

                    $query['nextUrl']  = $nextUrl;
                }

                if ($progress) {
                    $progress->finish();
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for getLeads: '.$object);

        return $executed;
    }

    /**
     * @param array $params
     * @param null  $query
     * @param null  $executed
     *
     * @return array|null
     */
    public function getCompanies($params = [], $query = null, $executed = null)
    {
        return $this->getLeads($params, $query, $executed, [], 'Account');
    }

    /**
     * @param array $params
     *
     * @return int|null
     *
     * @throws \Exception
     */
    public function pushLeadActivity($params = [])
    {
        $executed = null;

        $query  = $this->getFetchQuery($params);
        $config = $this->mergeConfigToFeatureSettings([]);

        /** @var SalesforceApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        $salesForceObjects[] = 'Lead';
        if (isset($config['objects']) && !empty($config['objects'])) {
            $salesForceObjects = $config['objects'];
        }

        // Ensure that Contact is attempted before Lead
        sort($salesForceObjects);

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $startDate             = new \DateTime($query['start']);
        $endDate               = new \DateTime($query['end']);
        $limit                 = 100;

        foreach ($salesForceObjects as $object) {
            if (!in_array($object, ['Contact', 'Lead'])) {
                continue;
            }

            try {
                if ($this->isAuthorized()) {
                    // Get first batch
                    $start         = 0;
                    $salesForceIds = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        $object,
                        'lead',
                        null,
                        $startDate->format('Y-m-d H:m:s'),
                        $endDate->format('Y-m-d H:m:s'),
                        true,
                        $start,
                        $limit
                    );
                    while (!empty($salesForceIds)) {
                        $executed += count($salesForceIds);

                        // Extract a list of lead Ids
                        $leadIds = [];
                        $sfIds   = [];
                        foreach ($salesForceIds as $ids) {
                            $leadIds[] = $ids['internal_entity_id'];
                            $sfIds[]   = $ids['integration_entity_id'];
                        }

                        // Collect lead activity for this batch
                        $leadActivity = $this->getLeadData(
                            $startDate,
                            $endDate,
                            $leadIds
                        );

                        $this->logger->debug('SALESFORCE: Syncing activity for '.count($leadActivity).' contacts ('.implode(', ', array_keys($leadActivity)).')');
                        $this->logger->debug('SALESFORCE: Syncing activity for '.var_export($sfIds, true));

                        $salesForceLeadData = [];
                        foreach ($salesForceIds as $ids) {
                            $leadId = $ids['internal_entity_id'];
                            if (isset($leadActivity[$leadId])) {
                                $sfId                                 = $ids['integration_entity_id'];
                                $salesForceLeadData[$sfId]            = $leadActivity[$leadId];
                                $salesForceLeadData[$sfId]['id']      = $ids['integration_entity_id'];
                                $salesForceLeadData[$sfId]['leadId']  = $ids['internal_entity_id'];
                                $salesForceLeadData[$sfId]['leadUrl'] = $this->router->generate(
                                    'mautic_plugin_timeline_view',
                                    ['integration' => 'Salesforce', 'leadId' => $leadId],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                );
                            } else {
                                $this->logger->debug('SALESFORCE: No activity found for contact ID '.$leadId);
                            }
                        }

                        if (!empty($salesForceLeadData)) {
                            $apiHelper->createLeadActivity($salesForceLeadData, $object);
                        } else {
                            $this->logger->debug('SALESFORCE: No contact activity to sync');
                        }

                        // Get the next batch
                        $start += $limit;
                        $salesForceIds = $integrationEntityRepo->getIntegrationsEntityId(
                            'Salesforce',
                            $object,
                            'lead',
                            null,
                            $startDate->format('Y-m-d H:m:s'),
                            $endDate->format('Y-m-d H:m:s'),
                            true,
                            $start,
                            $limit
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);
            }
        }

        return $executed;
    }

    /**
     * Return key recognized by integration.
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        $search = [];
        foreach ($this->objects as $object) {
            $search[] = '__'.$object;
        }

        return str_replace($search, '', $key);
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        $limit                   = (isset($params['limit'])) ? $params['limit'] : 100;
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $config                  = $this->mergeConfigToFeatureSettings($params);
        $integrationEntityRepo   = $this->getIntegrationEntityRepository();

        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors  = 0;

        list($fieldMapping, $mauticLeadFieldString, $supportedObjects) = $this->prepareFieldsForPush($config);

        if (empty($fieldMapping)) {
            return [0, 0, 0, 0];
        }

        $originalLimit = $limit;
        $progress      = false;

        // Get a total number of contacts to be updated and/or created for the progress counter
        $totalToUpdate = array_sum(
            $integrationEntityRepo->findLeadsToUpdate(
                'Salesforce',
                'lead',
                $mauticLeadFieldString,
                false,
                $fromDate,
                $toDate,
                $supportedObjects,
                []
            )
        );
        $totalToCreate = (in_array('Lead', $supportedObjects)) ? $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticLeadFieldString,
            false,
            $fromDate,
            $toDate
        ) : 0;
        $totalCount = $totalToProcess = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        // Start with contacts so we know who is a contact when we go to process converted leads
        if (count($supportedObjects) > 1) {
            $sfObject = 'Contact';
        } else {
            // Only Lead or Contact is enabled so start with which ever that is
            reset($supportedObjects);
            $sfObject = key($supportedObjects);
        }
        $noMoreUpdates   = false;
        $trackedContacts = [
            'Contact' => [],
            'Lead'    => [],
        ];

        // Loop to maximize composite that may include updating contacts, updating leads, and creating leads
        while ($totalCount > 0) {
            $limit           = $originalLimit;
            $mauticData      = [];
            $checkEmailsInSF = [];
            $leadsToSync     = [];
            $processedLeads  = [];

            // Process the updates
            if (!$noMoreUpdates) {
                $noMoreUpdates = $this->getMauticContactsToUpdate(
                    $checkEmailsInSF,
                    $mauticLeadFieldString,
                    $sfObject,
                    $trackedContacts,
                    $limit,
                    $fromDate,
                    $toDate,
                    $totalCount
                );

                if ($noMoreUpdates && 'Contact' === $sfObject && isset($supportedObjects['Lead'])) {
                    // Try Leads
                    $sfObject      = 'Lead';
                    $noMoreUpdates = $this->getMauticContactsToUpdate(
                        $checkEmailsInSF,
                        $mauticLeadFieldString,
                        $sfObject,
                        $trackedContacts,
                        $limit,
                        $fromDate,
                        $toDate,
                        $totalCount
                    );
                }

                if ($limit) {
                    // Mainly done for test mocking purposes
                    $limit = $this->getSalesforceSyncLimit($checkEmailsInSF, $limit);
                }
            }

            // If there is still room - grab Mautic leads to create if the Lead object is enabled
            $sfEntityRecords = [];
            if ('Lead' === $sfObject && (null === $limit || $limit > 0) && !empty($mauticLeadFieldString)) {
                try {
                    $sfEntityRecords = $this->getMauticContactsToCreate(
                        $checkEmailsInSF,
                        $fieldMapping,
                        $mauticLeadFieldString,
                        $limit,
                        $fromDate,
                        $toDate,
                        $totalCount,
                        $progress
                    );
                } catch (ApiErrorException $exception) {
                    $this->cleanupFromSync($leadsToSync, $exception);
                }
            } elseif ($checkEmailsInSF) {
                $sfEntityRecords = $this->getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, implode(',', array_keys($fieldMapping[$sfObject]['create'])));

                if (!isset($sfEntityRecords['records'])) {
                    // Something is wrong so throw an exception to prevent creating a bunch of new leads
                    $this->cleanupFromSync(
                        $leadsToSync,
                        json_encode($sfEntityRecords)
                    );
                }
            }

            $this->pushLeadDoNotContactByDate('email', $checkEmailsInSF, $sfObject, $params);

            // We're done
            if (!$checkEmailsInSF) {
                break;
            }

            $this->prepareMauticContactsToUpdate(
                $mauticData,
                $checkEmailsInSF,
                $processedLeads,
                $trackedContacts,
                $leadsToSync,
                $fieldMapping,
                $mauticLeadFieldString,
                $sfEntityRecords,
                $progress
            );

            // Only create left over if Lead object is enabled in integration settings
            if ($checkEmailsInSF && isset($fieldMapping['Lead'])) {
                $this->prepareMauticContactsToCreate(
                    $mauticData,
                    $checkEmailsInSF,
                    $processedLeads,
                    $fieldMapping
                );
            }
            // Persist pending changes
            $this->cleanupFromSync($leadsToSync);
            // Make the request
            $this->makeCompositeRequest($mauticData, $totalUpdated, $totalCreated, $totalErrors);

            // Stop gap - if 100% let's kill the script
            if ($progress && $progress->getProgressPercent() >= 1) {
                break;
            }
        }

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for pushLeads');

        // Assume that those not touched are ignored due to not having matching fields, duplicates, etc
        $totalIgnored = $totalToProcess - ($totalUpdated + $totalCreated + $totalErrors);

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    /**
     * @param $lead
     *
     * @return array
     */
    public function getSalesforceLeadId($lead)
    {
        $config                = $this->mergeConfigToFeatureSettings([]);
        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        if (isset($config['objects'])) {
            //try searching for lead as this has been changed before in updated done to the plugin
            if (false !== array_search('Contact', $config['objects'])) {
                $resultContact = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', 'Contact', 'lead', $lead->getId());

                if ($resultContact) {
                    return $resultContact;
                }
            }
        }
        $resultLead = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', 'Lead', 'lead', $lead->getId());

        return $resultLead;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getCampaigns()
    {
        $campaigns = [];
        try {
            $campaigns = $this->getApiHelper()->getCampaigns();
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $campaigns;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getCampaignChoices()
    {
        $choices   = [];
        $campaigns = $this->getCampaigns();

        if (!empty($campaigns['records'])) {
            foreach ($campaigns['records'] as $campaign) {
                $choices[] = [
                    'value' => $campaign['Id'],
                    'label' => $campaign['Name'],
                ];
            }
        }

        return $choices;
    }

    /**
     * @param $campaignId
     *
     * @throws \Exception
     */
    public function getCampaignMembers($campaignId)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $mixedFields           = $this->getIntegrationSettings()->getFeatureSettings();

        // Get the last time the campaign was synced to prevent resyncing the entire SF campaign
        $cacheKey     = $this->getName().'.CampaignSync.'.$campaignId;
        $lastSyncDate = $this->getCache()->get($cacheKey);
        $syncStarted  = (new \DateTime())->format('c');

        if (false === $lastSyncDate) {
            // Sync all records
            $lastSyncDate = null;
        }

        // Consume in batches
        $paginator      = new ResultsPaginator($this->logger, $this->keys['instance_url']);
        $nextRecordsUrl = null;

        while (true) {
            try {
                $results = $this->getApiHelper()->getCampaignMembers($campaignId, $lastSyncDate, $nextRecordsUrl);
                $paginator->setResults($results);

                $organizer = new Organizer($results['records']);
                $fetcher   = new Fetcher($integrationEntityRepo, $organizer, $campaignId);

                // Create Mautic contacts from Campaign Members if they don't already exist
                foreach (['Contact', 'Lead'] as $object) {
                    $fields = $this->getMixedLeadFields($mixedFields, $object);

                    try {
                        $query = $fetcher->getQueryForUnknownObjects($fields, $object);
                        $this->getLeads([], $query, $executed, [], $object);
                    } catch (NoObjectsToFetchException $exception) {
                        // No more IDs to fetch so break and continue on
                        continue;
                    }
                }

                // Create integration entities for members we aren't already tracking
                $unknownMembers  = $fetcher->getUnknownCampaignMembers();
                $persistEntities = [];
                $counter         = 0;

                foreach ($unknownMembers as $mauticContactId) {
                    $persistEntities[] = $this->createIntegrationEntity(
                        CampaignMember::OBJECT,
                        $campaignId,
                        'lead',
                        $mauticContactId,
                        [],
                        false
                    );

                    ++$counter;

                    if (20 === $counter) {
                        // Batch to control RAM use
                        $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($persistEntities);
                        $this->em->clear(IntegrationEntity::class);
                        $persistEntities = [];
                        $counter         = 0;
                    }
                }

                // Catch left overs
                if ($persistEntities) {
                    $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($persistEntities);
                    $this->em->clear(IntegrationEntity::class);
                }

                unset($unknownMembers, $fetcher, $organizer, $persistEntities);

                // Do we continue?
                if (!$nextRecordsUrl = $paginator->getNextResultsUrl()) {
                    // No more results to fetch

                    // Store the latest sync date at the end in case something happens during the actual sync process and it needs to be re-ran
                    $this->cache->set($cacheKey, $syncStarted);

                    break;
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);

                break;
            }
        }
    }

    /**
     * @param $fields
     * @param $object
     *
     * @return array
     */
    public function getMixedLeadFields($fields, $object)
    {
        $mixedFields = array_filter($fields['leadFields']);
        $fields      = [];
        foreach ($mixedFields as $sfField => $mField) {
            if (strpos($sfField, '__'.$object) !== false) {
                $fields[] = str_replace('__'.$object, '', $sfField);
            }
            if (strpos($sfField, '-'.$object) !== false) {
                $fields[] = str_replace('-'.$object, '', $sfField);
            }
        }

        return $fields;
    }

    /**
     * @param $campaignId
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCampaignMemberStatus($campaignId)
    {
        $campaignMemberStatus = [];
        try {
            $campaignMemberStatus = $this->getApiHelper()->getCampaignMemberStatus($campaignId);
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $campaignMemberStatus;
    }

    /**
     * @param Lead $lead
     * @param      $campaignId
     * @param      $status
     *
     * @return array
     */
    public function pushLeadToCampaign(Lead $lead, $campaignId, $status = '', $personIds = null)
    {
        if (empty($personIds)) {
            // personIds should have been generated by pushLead()

            return false;
        }

        $mauticData = [];
        $objectId   = null;

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');

        $body = [
            'Status' => $status,
        ];
        $object = 'CampaignMember';
        $url    = '/services/data/v38.0/sobjects/'.$object;

        if (!empty($lead->getEmail())) {
            $pushPeople = [];
            $pushObject = null;
            if (!empty($personIds)) {
                // Give precendence to Contact CampaignMembers
                if (!empty($personIds['Contact'])) {
                    $pushObject      = 'Contact';
                    $campaignMembers = $this->getApiHelper()->checkCampaignMembership($campaignId, $pushObject, $personIds[$pushObject]);
                    $pushPeople      = $personIds[$pushObject];
                }

                if (empty($campaignMembers) && !empty($personIds['Lead'])) {
                    $pushObject      = 'Lead';
                    $campaignMembers = $this->getApiHelper()->checkCampaignMembership($campaignId, $pushObject, $personIds[$pushObject]);
                    $pushPeople      = $personIds[$pushObject];
                }
            } // pushLead should have handled this

            foreach ($pushPeople as $memberId) {
                $campaignMappingId = '-'.$campaignId;

                if (isset($campaignMembers[$memberId])) {
                    $existingCampaignMember = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        'CampaignMember',
                        'lead',
                        null,
                        null,
                        null,
                        false,
                        0,
                        0,
                        [$campaignMembers[$memberId]]
                    );
                    if ($existingCampaignMember) {
                        foreach ($existingCampaignMember as $member) {
                            $integrationEntity = $integrationEntityRepo->getEntity($member['id']);
                            $referenceId       = $integrationEntity->getId();
                            $internalLeadId    = $integrationEntity->getInternalEntityId();
                        }
                    }
                    $id = !empty($lead->getId()) ? $lead->getId() : '';
                    $id .= '-CampaignMember'.$campaignMembers[$memberId];
                    $id .= !empty($referenceId) ? '-'.$referenceId : '';
                    $id .= $campaignMappingId;
                    $patchurl        = $url.'/'.$campaignMembers[$memberId];
                    $mauticData[$id] = [
                        'method'      => 'PATCH',
                        'url'         => $patchurl,
                        'referenceId' => $id,
                        'body'        => $body,
                        'httpHeaders' => [
                            'Sforce-Auto-Assign' => 'FALSE',
                        ],
                    ];
                } else {
                    $id              = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMemberNew-null'.$campaignMappingId;
                    $mauticData[$id] = [
                        'method'      => 'POST',
                        'url'         => $url,
                        'referenceId' => $id,
                        'body'        => array_merge(
                            $body,
                            [
                                'CampaignId'      => $campaignId,
                                "{$pushObject}Id" => $memberId,
                            ]
                        ),
                    ];
                }
            }

            $request['allOrNone']        = 'false';
            $request['compositeRequest'] = array_values($mauticData);

            $this->logger->debug('SALESFORCE: pushLeadToCampaign '.var_export($request, true));

            if (!empty($request)) {
                $result = $this->getApiHelper()->syncMauticToSalesforce($request);

                return (bool) array_sum($this->processCompositeResponse($result['compositeResponse']));
            }
        }

        return false;
    }

    /**
     * @param $email
     *
     * @return mixed|string
     */
    protected function getSyncKey($email)
    {
        return mb_strtolower($this->cleanPushData($email));
    }

    /**
     * @param $checkEmailsInSF
     * @param $mauticLeadFieldString
     * @param $sfObject
     * @param $trackedContacts
     * @param $limit
     * @param $fromDate
     * @param $toDate
     * @param $totalCount
     *
     * @return bool
     */
    protected function getMauticContactsToUpdate(
        &$checkEmailsInSF,
        $mauticLeadFieldString,
        &$sfObject,
        &$trackedContacts,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount
    ) {
        // Fetch them separately so we can determine if Leads are already Contacts
        $toUpdate = $this->getIntegrationEntityRepository()->findLeadsToUpdate(
            'Salesforce',
            'lead',
            $mauticLeadFieldString,
            $limit,
            $fromDate,
            $toDate,
            $sfObject
        )[$sfObject];

        $toUpdateCount = count($toUpdate);
        $totalCount -= $toUpdateCount;

        foreach ($toUpdate as $lead) {
            if (!empty($lead['email'])) {
                $lead                                               = $this->getCompoundMauticFields($lead);
                $key                                                = $this->getSyncKey($lead['email']);
                $trackedContacts[$lead['integration_entity']][$key] = $lead['id'];

                if ('Contact' == $sfObject) {
                    $this->setContactToSync($checkEmailsInSF, $lead);
                } elseif (isset($trackedContacts['Contact'][$key])) {
                    // We already know this is a converted contact so just ignore it
                    $integrationEntity = $this->em->getReference(
                        'MauticPluginBundle:IntegrationEntity',
                        $lead['id']
                    );
                    $this->deleteIntegrationEntities[] = $integrationEntity;
                    $this->logger->debug('SALESFORCE: Converted lead '.$lead['email']);
                } else {
                    $this->setContactToSync($checkEmailsInSF, $lead);
                }
            }
        }

        return 0 === $toUpdateCount;
    }

    /**
     * @param      $checkEmailsInSF
     * @param      $fieldMapping
     * @param      $mauticLeadFieldString
     * @param      $limit
     * @param      $fromDate
     * @param      $toDate
     * @param      $totalCount
     * @param null $progress
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    protected function getMauticContactsToCreate(
        &$checkEmailsInSF,
        $fieldMapping,
        $mauticLeadFieldString,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount,
        $progress = null
    ) {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $leadsToCreate         = $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticLeadFieldString,
            $limit,
            $fromDate,
            $toDate
        );
        $totalCount -= count($leadsToCreate);
        $foundContacts   = [];
        $sfEntityRecords = [
            'totalSize' => 0,
            'records'   => [],
        ];
        $error = false;

        foreach ($leadsToCreate as $lead) {
            $lead = $this->getCompoundMauticFields($lead);

            if (isset($lead['email'])) {
                $this->setContactToSync($checkEmailsInSF, $lead);
            } elseif ($progress) {
                $progress->advance();
            }
        }

        // When creating, we have to check for Contacts first then Lead
        if (isset($fieldMapping['Contact'])) {
            $sfEntityRecords = $this->getSalesforceObjectsByEmails('Contact', $checkEmailsInSF, implode(',', array_keys($fieldMapping['Contact']['create'])));
            if (isset($sfEntityRecords['records'])) {
                foreach ($sfEntityRecords['records'] as $sfContactRecord) {
                    if (!isset($sfContactRecord['Email'])) {
                        continue;
                    }
                    $key                 = $this->getSyncKey($sfContactRecord['Email']);
                    $foundContacts[$key] = $key;
                }
            } else {
                $error = json_encode($sfEntityRecords);
            }
        }

        // For any Mautic contacts left over, check to see if existing Leads exist
        if (isset($fieldMapping['Lead']) && $checkSfLeads = array_diff_key($checkEmailsInSF, $foundContacts)) {
            $sfLeadRecords = $this->getSalesforceObjectsByEmails('Lead', $checkSfLeads, implode(',', array_keys($fieldMapping['Lead']['create'])));

            if (isset($sfLeadRecords['records'])) {
                // Merge contact records with these
                $sfEntityRecords['records']   = array_merge($sfEntityRecords['records'], $sfLeadRecords['records']);
                $sfEntityRecords['totalSize'] = (int) $sfEntityRecords['totalSize'] + (int) $sfLeadRecords['totalSize'];
            } else {
                $error = json_encode($sfLeadRecords);
            }
        }

        if ($error) {
            throw new ApiErrorException($error);
        }

        unset($leadsToCreate, $checkSfLeads);

        return $sfEntityRecords;
    }

    /**
     * @param      $mauticData
     * @param      $objectFields
     * @param      $object
     * @param      $lead
     * @param null $objectId
     * @param null $sfRecord
     *
     * @return array
     */
    protected function buildCompositeBody(
        &$mauticData,
        $objectFields,
        $object,
        &$entity,
        $objectId = null,
        $sfRecord = null
    ) {
        $body         = [];
        $updateEntity = [];
        $company      = null;
        $config       = $this->mergeConfigToFeatureSettings([]);

        if ((isset($entity['email']) && !empty($entity['email'])) || (isset($entity['companyname']) && !empty($entity['companyname']))) {
            //use a composite patch here that can update and create (one query) every 200 records
            if (isset($objectFields['update'])) {
                $fields = ($objectId) ? $objectFields['update'] : $objectFields['create'];
                if (isset($entity['company']) && isset($entity['integration_entity']) && $object == 'Contact') {
                    $accountId = $this->getCompanyName($entity['company'], 'Id', 'Name');

                    if (!$accountId) {
                        //company was not found so create a new company in Salesforce
                        $lead = $this->leadModel->getEntity($entity['internal_entity_id']);
                        if ($lead) {
                            $companies = $this->leadModel->getCompanies($lead);
                            if (!empty($companies)) {
                                foreach ($companies as $companyData) {
                                    if ($companyData['is_primary']) {
                                        $company = $this->companyModel->getEntity($companyData['company_id']);
                                    }
                                }
                                if ($company) {
                                    $sfCompany = $this->pushCompany($company);
                                    if (!empty($sfCompany)) {
                                        $entity['company'] = key($sfCompany);
                                    }
                                }
                            } else {
                                unset($entity['company']);
                            }
                        }
                    } else {
                        $entity['company'] = $accountId;
                    }
                }
                $fields = $this->getBlankFieldsToUpdate($fields, $sfRecord, $objectFields, $config);
            } else {
                $fields = $objectFields;
            }

            foreach ($fields as $sfField => $mauticField) {
                if (isset($entity[$mauticField])) {
                    $fieldType = (isset($objectFields['types']) && isset($objectFields['types'][$sfField])) ? $objectFields['types'][$sfField]
                        : 'string';
                    if (!empty($entity[$mauticField]) and $fieldType != 'boolean') {
                        $body[$sfField] = $this->cleanPushData($entity[$mauticField], $fieldType);
                    } elseif ($fieldType == 'boolean') {
                        $body[$sfField] = $this->cleanPushData($entity[$mauticField], $fieldType);
                    }
                }
                if (array_key_exists($sfField, $objectFields['required']['fields']) && empty($body[$sfField])) {
                    if (isset($sfRecord[$sfField])) {
                        $body[$sfField] = $sfRecord[$sfField];
                        if (empty($entity[$mauticField]) && !empty($sfRecord[$sfField])
                            && $sfRecord[$sfField] !== $this->translator->trans(
                                'mautic.integration.form.lead.unknown'
                            )
                        ) {
                            $updateEntity[$mauticField] = $sfRecord[$sfField];
                        }
                    } else {
                        $body[$sfField] = $this->translator->trans('mautic.integration.form.lead.unknown');
                    }
                }
            }

            $this->amendLeadDataBeforePush($body);

            if (!empty($body)) {
                $url = '/services/data/v38.0/sobjects/'.$object;
                if ($objectId) {
                    $url .= '/'.$objectId;
                }
                $id              = $entity['internal_entity_id'].'-'.$object.(!empty($entity['id']) ? '-'.$entity['id'] : '');
                $method          = ($objectId) ? 'PATCH' : 'POST';
                $mauticData[$id] = [
                    'method'      => $method,
                    'url'         => $url,
                    'referenceId' => $id,
                    'body'        => $body,
                    'httpHeaders' => [
                        'Sforce-Auto-Assign' => ($objectId) ? 'FALSE' : 'TRUE',
                    ],
                ];
            }
        }

        return $updateEntity;
    }

    /**
     * @param array $config
     * @param array $availableFields
     * @param       $object
     *
     * @return array
     */
    protected function getRequiredFieldString(array $config, array $availableFields, $object)
    {
        $requiredFields = $this->getRequiredFields($availableFields[$object]);

        if ($object != 'company') {
            $requiredFields = $this->prepareFieldsForSync($config['leadFields'], array_keys($requiredFields), $object);
        }

        $requiredString = implode(',', array_keys($requiredFields));

        return [$requiredFields, $requiredString];
    }

    /**
     * @param $config
     *
     * @return array
     */
    protected function prepareFieldsForPush($config)
    {
        $leadFields = array_unique(array_values($config['leadFields']));
        $leadFields = array_combine($leadFields, $leadFields);
        unset($leadFields['mauticContactTimelineLink']);
        unset($leadFields['mauticContactIsContactableByEmail']);

        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config);
        $fieldKeys          = array_keys($config['leadFields']);
        $supportedObjects   = [];
        $objectFields       = [];

        // Important to have contacts first!!
        if (false !== array_search('Contact', $config['objects'])) {
            $supportedObjects['Contact'] = 'Contact';
            $fieldsToCreate              = $this->prepareFieldsForSync($config['leadFields'], $fieldKeys, 'Contact');
            $objectFields['Contact']     = [
                'update' => isset($fieldsToUpdateInSf['Contact']) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Contact']) : [],
                'create' => $fieldsToCreate,
            ];
        }
        if (false !== array_search('Lead', $config['objects'])) {
            $supportedObjects['Lead'] = 'Lead';
            $fieldsToCreate           = $this->prepareFieldsForSync($config['leadFields'], $fieldKeys, 'Lead');
            $objectFields['Lead']     = [
                'update' => isset($fieldsToUpdateInSf['Lead']) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Lead']) : [],
                'create' => $fieldsToCreate,
            ];
        }

        $mauticLeadFieldString = implode(', l.', $leadFields);
        $mauticLeadFieldString = 'l.'.$mauticLeadFieldString;
        $availableFields       = $this->getAvailableLeadFields(['feature_settings' => ['objects' => $supportedObjects]]);

        // Setup required fields and field types
        foreach ($supportedObjects as $object) {
            $objectFields[$object]['types'] = [];
            if (isset($availableFields[$object])) {
                $fieldData = $this->prepareFieldsForSync($availableFields[$object], array_keys($availableFields[$object]), $object);
                foreach ($fieldData as $fieldName => $field) {
                    $objectFields[$object]['types'][$fieldName] = (isset($field['type'])) ? $field['type'] : 'string';
                }
            }

            list($fields, $string) = $this->getRequiredFieldString(
                $config,
                $availableFields,
                $object
            );

            $objectFields[$object]['required'] = [
                'fields' => $fields,
                'string' => $string,
            ];
        }

        return [$objectFields, $mauticLeadFieldString, $supportedObjects];
    }

    /**
     * @param        $config
     * @param null   $object
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForMautic($config, $object = null, $priorityObject = 'mautic')
    {
        $fields = parent::getPriorityFieldsForMautic($config, $object, $priorityObject);

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
    }

    /**
     * @param        $config
     * @param null   $object
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForIntegration($config, $object = null, $priorityObject = 'mautic')
    {
        $fields = parent::getPriorityFieldsForIntegration($config, $object, $priorityObject);
        unset($fields['Contact']['Id'], $fields['Lead']['Id']);

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
    }

    /**
     * @param     $response
     * @param int $totalUpdated
     * @param int $totalCreated
     * @param int $totalErrored
     *
     * @return array
     */
    protected function processCompositeResponse($response, &$totalUpdated = 0, &$totalCreated = 0, &$totalErrored = 0)
    {
        if (is_array($response)) {
            foreach ($response as $item) {
                $contactId      = $integrationEntityId      = $campaignId      = null;
                $object         = 'Lead';
                $internalObject = 'lead';
                if (!empty($item['referenceId'])) {
                    $reference = explode('-', $item['referenceId']);
                    if (3 === count($reference)) {
                        list($contactId, $object, $integrationEntityId) = $reference;
                    } elseif (4 === count($reference)) {
                        list($contactId, $object, $integrationEntityId, $campaignId) = $reference;
                    } else {
                        list($contactId, $object) = $reference;
                    }
                }
                if (strstr($object, 'CampaignMember')) {
                    $object = 'CampaignMember';
                }
                if ($object == 'Account') {
                    $internalObject = 'company';
                }
                if (isset($item['body'][0]['errorCode'])) {
                    $exception = new ApiErrorException($item['body'][0]['message']);
                    if ($object == 'Contact' || $object = 'Lead') {
                        $exception->setContactId($contactId);
                    }
                    $this->logIntegrationError($exception);
                    $integrationEntity = null;
                    if ($integrationEntityId && $object !== 'CampaignMember') {
                        $integrationEntity = $this->integrationEntityModel->getEntityByIdAndSetSyncDate($integrationEntityId, new \DateTime());
                    } elseif (isset($campaignId)) {
                        $integrationEntity = $this->integrationEntityModel->getEntityByIdAndSetSyncDate($campaignId, $this->getLastSyncDate());
                    } elseif ($contactId) {
                        $integrationEntity = $this->createIntegrationEntity(
                            $object,
                            null,
                            $internalObject.'-error',
                            $contactId,
                            null,
                            false
                        );
                    }

                    if ($integrationEntity) {
                        $integrationEntity->setInternalEntity('ENTITY_IS_DELETED' === $item['body'][0]['errorCode'] ? $internalObject.'-deleted' : $internalObject.'-error')
                            ->setInternal(['error' => $item['body'][0]['message']]);
                        $this->persistIntegrationEntities[] = $integrationEntity;
                    }
                    ++$totalErrored;
                } elseif (!empty($item['body']['success'])) {
                    if (201 === $item['httpStatusCode']) {
                        // New object created
                        if ($object === 'CampaignMember') {
                            $internal = ['Id' => $item['body']['id']];
                        } else {
                            $internal = [];
                        }
                        $this->salesforceIdMapping[$contactId] = $item['body']['id'];
                        $this->persistIntegrationEntities[]    = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            $internalObject,
                            $contactId,
                            $internal,
                            false
                        );
                    }
                    ++$totalCreated;
                } elseif (204 === $item['httpStatusCode']) {
                    // Record was updated
                    if ($integrationEntityId) {
                        $integrationEntity = $this->integrationEntityModel->getEntityByIdAndSetSyncDate($integrationEntityId, $this->getLastSyncDate());
                        if ($integrationEntity) {
                            if (isset($this->salesforceIdMapping[$contactId])) {
                                $integrationEntity->setIntegrationEntityId($this->salesforceIdMapping[$contactId]);
                            }

                            $this->persistIntegrationEntities[] = $integrationEntity;
                        }
                    } elseif (!empty($this->salesforceIdMapping[$contactId])) {
                        // Found in Salesforce so create a new record for it
                        $this->persistIntegrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            $internalObject,
                            $contactId,
                            [],
                            false
                        );
                    }

                    ++$totalUpdated;
                } else {
                    $error = 'http status code '.$item['httpStatusCode'];
                    switch (true) {
                        case !empty($item['body'][0]['message']['message']):
                            $error = $item['body'][0]['message']['message'];
                            break;
                        case !empty($item['body']['message']):
                            $error = $item['body']['message'];
                            break;
                    }

                    $exception = new ApiErrorException($error);
                    if (!empty($item['referenceId']) && ($object == 'Contact' || $object = 'Lead')) {
                        $exception->setContactId($item['referenceId']);
                    }
                    $this->logIntegrationError($exception);
                    ++$totalErrored;

                    if ($integrationEntityId) {
                        $integrationEntity = $this->integrationEntityModel->getEntityByIdAndSetSyncDate($integrationEntityId, $this->getLastSyncDate());
                        if ($integrationEntity) {
                            if (isset($this->salesforceIdMapping[$contactId])) {
                                $integrationEntity->setIntegrationEntityId($this->salesforceIdMapping[$contactId]);
                            }

                            $this->persistIntegrationEntities[] = $integrationEntity;
                        }
                    } elseif (!empty($this->salesforceIdMapping[$contactId])) {
                        // Found in Salesforce so create a new record for it
                        $this->persistIntegrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            $internalObject,
                            $contactId,
                            [],
                            false
                        );
                    }
                }
            }
        }

        $this->cleanupFromSync();

        return [$totalUpdated, $totalCreated];
    }

    /**
     * @param $sfObject
     * @param $checkEmailsInSF
     * @param $requiredFieldString
     *
     * @return array
     */
    protected function getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, $requiredFieldString)
    {
        // Salesforce craps out with double quotes and unescaped single quotes
        $findEmailsInSF = array_map(
            function ($lead) {
                return str_replace("'", "\'", $this->cleanPushData($lead['email']));
            },
            $checkEmailsInSF
        );

        $fieldString = "'".implode("','", $findEmailsInSF)."'";
        $queryUrl    = $this->getQueryUrl();
        $findQuery   = ('Lead' === $sfObject)
            ?
            'select Id, '.$requiredFieldString.', ConvertedContactId from Lead where isDeleted = false and Email in ('.$fieldString.')'
            :
            'select Id, '.$requiredFieldString.' from Contact where isDeleted = false and Email in ('.$fieldString.')';

        return $this->getApiHelper()->request('query', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param      $mauticData
     * @param      $checkEmailsInSF
     * @param      $processedLeads
     * @param      $trackedContacts
     * @param      $leadsToSync
     * @param      $requiredFields
     * @param      $objectFields
     * @param      $mauticLeadFieldString
     * @param      $sfEntityRecords
     * @param null $progress
     */
    protected function prepareMauticContactsToUpdate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        &$trackedContacts,
        &$leadsToSync,
        $objectFields,
        $mauticLeadFieldString,
        $sfEntityRecords,
        $progress = null
    ) {
        foreach ($sfEntityRecords['records'] as $sfKey => $sfEntityRecord) {
            $skipObject = false;
            $syncLead   = false;
            $sfObject   = $sfEntityRecord['attributes']['type'];
            if (!isset($sfEntityRecord['Email'])) {
                // This is a record we don't recognize so continue
                return;
            }
            $key = $this->getSyncKey($sfEntityRecord['Email']);
            if (!isset($sfEntityRecord['Id']) || (!isset($checkEmailsInSF[$key]) && !isset($processedLeads[$key]))) {
                // This is a record we don't recognize so continue
                return;
            }

            $leadData  = (isset($processedLeads[$key])) ? $processedLeads[$key] : $checkEmailsInSF[$key];
            $contactId = $leadData['internal_entity_id'];

            if (
                isset($checkEmailsInSF[$key])
                && (
                    (
                        'Lead' === $sfObject && !empty($sfEntityRecord['ConvertedContactId'])
                    )
                    || (
                        isset($checkEmailsInSF[$key]['integration_entity']) && 'Contact' === $sfObject
                        && 'Lead' === $checkEmailsInSF[$key]['integration_entity']
                    )
                )
            ) {
                $deleted = false;
                // This is a converted lead so remove the Lead entity leaving the Contact entity
                if (!empty($trackedContacts['Lead'][$key])) {
                    $this->deleteIntegrationEntities[] = $this->em->getReference(
                        'MauticPluginBundle:IntegrationEntity',
                        $trackedContacts['Lead'][$key]
                    );
                    $deleted = true;
                    unset($trackedContacts['Lead'][$key]);
                }

                if ($contactEntity = $this->checkLeadIsContact($trackedContacts['Contact'], $key, $contactId, $mauticLeadFieldString)) {
                    // This Lead is already a Contact but was not updated for whatever reason
                    if (!$deleted) {
                        $this->deleteIntegrationEntities[] = $this->em->getReference(
                            'MauticPluginBundle:IntegrationEntity',
                            $checkEmailsInSF[$key]['id']
                        );
                    }

                    // Update the Contact record instead
                    $checkEmailsInSF[$key]            = $contactEntity;
                    $trackedContacts['Contact'][$key] = $contactEntity['id'];
                } else {
                    $id = (!empty($sfEntityRecord['ConvertedContactId'])) ? $sfEntityRecord['ConvertedContactId'] : $sfEntityRecord['Id'];
                    // This contact does not have a Contact record
                    $integrationEntity = $this->createIntegrationEntity(
                        'Contact',
                        $id,
                        'lead',
                        $contactId
                    );

                    $checkEmailsInSF[$key]['integration_entity']    = 'Contact';
                    $checkEmailsInSF[$key]['integration_entity_id'] = $id;
                    $checkEmailsInSF[$key]['id']                    = $integrationEntity;
                }

                $this->logger->debug('SALESFORCE: Converted lead '.$sfEntityRecord['Email']);

                // skip if this is a Lead object since it'll be handled with the Contact entry
                if ('Lead' === $sfObject) {
                    unset($checkEmailsInSF[$key]);
                    unset($sfEntityRecords['records'][$sfKey]);
                    $skipObject = true;
                }
            }

            if (!$skipObject) {
                // Only progress if we have a unique Lead and not updating a Salesforce entry duplicate
                if (!isset($processedLeads[$key])) {
                    if ($progress) {
                        $progress->advance();
                    }

                    // Mark that this lead has been processed
                    $leadData = $processedLeads[$key] = $checkEmailsInSF[$key];
                }

                // Keep track of Mautic ID to Salesforce ID for the integration table
                $this->salesforceIdMapping[$contactId] = (!empty($sfEntityRecord['ConvertedContactId'])) ? $sfEntityRecord['ConvertedContactId']
                    : $sfEntityRecord['Id'];

                $leadEntity = $this->em->getReference('MauticLeadBundle:Lead', $leadData['internal_entity_id']);
                if ($updateLead = $this->buildCompositeBody(
                    $mauticData,
                    $objectFields[$sfObject],
                    $sfObject,
                    $leadData,
                    $sfEntityRecord['Id'],
                    $sfEntityRecord
                )
                ) {
                    // Get the lead entity
                    /* @var Lead $leadEntity */
                    foreach ($updateLead as $mauticField => $sfValue) {
                        $leadEntity->addUpdatedField($mauticField, $sfValue);
                    }

                    $syncLead = !empty($leadEntity->getChanges(true));
                }

                // Validate if we have a company for this Mautic contact
                if (!empty($sfEntityRecord['Company'])
                    && $sfEntityRecord['Company'] !== $this->translator->trans(
                        'mautic.integration.form.lead.unknown'
                    )
                ) {
                    $company = IdentifyCompanyHelper::identifyLeadsCompany(
                        ['company' => $sfEntityRecord['Company']],
                        null,
                        $this->companyModel
                    );

                    if (!empty($company[2])) {
                        $syncLead = $this->companyModel->addLeadToCompany($company[2], $leadEntity);
                        $this->em->detach($company[2]);
                    }
                }

                if ($syncLead) {
                    $leadsToSync[] = $leadEntity;
                } else {
                    $this->em->detach($leadEntity);
                }
            }

            unset($checkEmailsInSF[$key]);
        }
    }

    /**
     * @param $mauticData
     * @param $checkEmailsInSF
     * @param $processedLeads
     * @param $objectFields
     */
    protected function prepareMauticContactsToCreate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        $objectFields
    ) {
        foreach ($checkEmailsInSF as $key => $lead) {
            if (!empty($lead['integration_entity_id'])) {
                if ($this->buildCompositeBody(
                    $mauticData,
                    $objectFields[$lead['integration_entity']],
                    $lead['integration_entity'],
                    $lead,
                    $lead['integration_entity_id']
                )
                ) {
                    $this->logger->debug('SALESFORCE: Contact has existing ID so updating '.$lead['email']);
                }
            } else {
                $this->buildCompositeBody(
                    $mauticData,
                    $objectFields['Lead'],
                    'Lead',
                    $lead
                );
            }

            $processedLeads[$key] = $checkEmailsInSF[$key];
            unset($checkEmailsInSF[$key]);
        }
    }

    /**
     * @param     $mauticData
     * @param int $totalUpdated
     * @param int $totalCreated
     * @param int $totalErrored
     */
    protected function makeCompositeRequest($mauticData, &$totalUpdated = 0, &$totalCreated = 0, &$totalErrored = 0)
    {
        if (empty($mauticData)) {
            return;
        }

        /** @var SalesforceApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        // We can only send 25 at a time
        $request              = [];
        $request['allOrNone'] = 'false';
        $chunked              = array_chunk($mauticData, 25);

        foreach ($chunked as $chunk) {
            // We can only submit 25 at a time
            if ($chunk) {
                $request['compositeRequest'] = $chunk;
                $result                      = $apiHelper->syncMauticToSalesforce($request);
                $this->logger->debug('SALESFORCE: Sync Composite  '.var_export($request, true));
                $this->processCompositeResponse($result['compositeResponse'], $totalUpdated, $totalCreated, $totalErrored);
            }
        }
    }

    /**
     * @param $checkEmailsInSF
     * @param $lead
     *
     * @return bool|mixed|string
     */
    protected function setContactToSync(&$checkEmailsInSF, $lead)
    {
        $key = $this->getSyncKey($lead['email']);
        if (isset($checkEmailsInSF[$key])) {
            // this is a duplicate in Mautic
            $this->mauticDuplicates[$lead['internal_entity_id']] = 'lead-duplicate';

            return false;
        }

        $checkEmailsInSF[$key] = $lead;

        return $key;
    }

    /**
     * @param $currentContactList
     * @param $limit
     *
     * @return int
     */
    protected function getSalesforceSyncLimit($currentContactList, $limit)
    {
        $limit -= count($currentContactList);

        return $limit;
    }

    /**
     * @param $trackedContacts
     * @param $email
     * @param $contactId
     * @param $leadFields
     *
     * @return array|bool
     */
    protected function checkLeadIsContact(&$trackedContacts, $email, $contactId, $leadFields)
    {
        if (empty($trackedContacts[$email])) {
            // Check if there's an existing entry
            return $this->getIntegrationEntityRepository()->getIntegrationEntity(
                $this->getName(),
                'Contact',
                'lead',
                $contactId,
                $leadFields
            );
        }

        return false;
    }

    /**
     * @param       $fieldsToUpdate
     * @param array $objects
     *
     * @return array
     */
    protected function cleanPriorityFields($fieldsToUpdate, $objects = null)
    {
        if (null === $objects) {
            $objects = ['Lead', 'Contact'];
        }

        if (isset($fieldsToUpdate['leadFields'])) {
            // Pass in the whole config
            $fields = $fieldsToUpdate;
        } else {
            $fields = array_flip($fieldsToUpdate);
        }

        $fieldsToUpdate = $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);

        return $fieldsToUpdate;
    }

    /**
     * @param Lead $lead
     * @param      $config
     *
     * @return array
     */
    protected function mapContactDataForPush(Lead $lead, $config)
    {
        $fields             = array_keys($config['leadFields']);
        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config);
        $fieldMapping       = [
            'Lead'    => [],
            'Contact' => [],
        ];
        $mappedData = [
            'Lead'    => [],
            'Contact' => [],
        ];

        foreach (['Lead', 'Contact'] as $object) {
            if (isset($config['objects']) && false !== array_search($object, $config['objects'])) {
                $fieldMapping[$object]['create'] = $this->prepareFieldsForSync($config['leadFields'], $fields, $object);
                $fieldMapping[$object]['update'] = isset($fieldsToUpdateInSf[$object]) ? array_intersect_key(
                    $fieldMapping[$object]['create'],
                    $fieldsToUpdateInSf[$object]
                ) : [];

                // Create an update and
                $mappedData[$object]['create'] = $this->populateLeadData(
                    $lead,
                    [
                        'leadFields'       => $fieldMapping[$object]['create'], // map with all fields available
                        'object'           => $object,
                        'feature_settings' => [
                            'objects' => $config['objects'],
                        ],
                    ]
                );

                if (isset($mappedData[$object]['create']['Id'])) {
                    unset($mappedData[$object]['create']['Id']);
                }

                $this->amendLeadDataBeforePush($mappedData[$object]['create']);

                // Set the update fields
                $mappedData[$object]['update'] = array_intersect_key($mappedData[$object]['create'], $fieldMapping[$object]['update']);
            }
        }

        return $mappedData;
    }

    /**
     * @param Lead $lead
     * @param      $config
     *
     * @return array
     */
    protected function mapCompanyDataForPush(Company $company, $config)
    {
        $object     = 'company';
        $entity     = [];
        $mappedData = [
            $object => [],
        ];

        if (isset($config['objects']) && false !== array_search($object, $config['objects'])) {
            $fieldKeys          = array_keys($config['companyFields']);
            $fieldsToCreate     = $this->prepareFieldsForSync($config['companyFields'], $fieldKeys, 'Account');
            $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config, 'Account', 'mautic_company');

            $fieldMapping[$object] = [
                'update' => !empty($fieldsToUpdateInSf) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf) : [],
                'create' => $fieldsToCreate,
            ];
            $entity['primaryCompany'] = $company->getProfileFields();

            // Create an update and
            $mappedData[$object]['create'] = $this->populateCompanyData(
                $entity,
                [
                    'companyFields'    => $fieldMapping[$object]['create'], // map with all fields available
                    'object'           => $object,
                    'feature_settings' => [
                        'objects' => $config['objects'],
                    ],
                ]
            );

            if (isset($mappedData[$object]['create']['Id'])) {
                unset($mappedData[$object]['create']['Id']);
            }

            $this->amendLeadDataBeforePush($mappedData[$object]['create']);

            // Set the update fields
            $mappedData[$object]['update'] = array_intersect_key($mappedData[$object]['create'], $fieldMapping[$object]['update']);
        }

        return $mappedData;
    }

    /**
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
        $mappedData = StateValidationHelper::validate($mappedData);
    }

    /**
     * @param array $fields
     *
     * @return array
     *
     * @deprecated 2.6.0 to be removed in 3.0
     */
    public function amendToSfFields($fields)
    {
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getFieldsForQuery($object)
    {
        $fields = $this->getIntegrationSettings()->getFeatureSettings();
        switch ($object) {
            case 'company':
            case 'Account':
                $fields = array_keys(array_filter($fields['companyFields']));
                break;
            default:
                $mixedFields = array_filter($fields['leadFields']);
                $fields      = [];
                foreach ($mixedFields as $sfField => $mField) {
                    if (strpos($sfField, '__'.$object) !== false) {
                        $fields[] = str_replace('__'.$object, '', $sfField);
                    }
                    if (strpos($sfField, '-'.$object) !== false) {
                        $fields[] = str_replace('-'.$object, '', $sfField);
                    }
                }
        }

        return $fields;
    }

    /**
     * @param $sfObject
     * @param $sfFieldString
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getDncHistory($sfObject, $sfFieldString)
    {
        //get last modified date for donot contact in Salesforce
        $historySelect = 'Select Field, '.$sfObject.'Id, CreatedDate, isDeleted, NewValue from '.$sfObject.'History where Field = \'HasOptedOutOfEmail\' and '.$sfObject.'Id IN ('.$sfFieldString.') ORDER BY CreatedDate DESC';
        $queryUrl      = $this->getQueryUrl();
        $historySF     = $this->getApiHelper()->request('query', ['q' => $historySelect], 'GET', false, null, $queryUrl);

        return $historySF;
    }

    /**
     * Update the record in each system taking the last modified record.
     *
     * @param $leadId
     * @param string $channel
     * @param string $sfObject
     * @param array  $sfIds
     *
     * @return int
     *
     * @throws ApiErrorException
     */
    public function pushLeadDoNotContactByDate($channel, &$sfRecords, $sfObject, $params = [])
    {
        $filters = [];
        $leadIds = [];

        if (empty($sfRecords) || !isset($sfRecords['mauticContactIsContactableByEmail']) && !$this->updateDncByDate()) {
            return;
        }

        foreach ($sfRecords as $leadEmail => $record) {
            if (empty($record['integration_entity_id'])) {
                continue;
            }

            $leadIds[$record['internal_entity_id']]    = $record['integration_entity_id'];
            $leadEmails[$record['internal_entity_id']] = $record['email'];
        }

        $sfFieldString = "'".implode("','", $leadIds)."'";

        $historySF = $this->getDncHistory($sfObject, $sfFieldString);
        //if there is no records of when it was modified in SF then just exit
        if (empty($historySF['records'])) {
            return;
        }

        //get last modified date for donot contact in Mautic
        $auditLogRepo        = $this->em->getRepository('MauticCoreBundle:AuditLog');
        $filters['search']   = 'dnc_channel_status%'.$channel;
        $lastModifiedDNCDate = $auditLogRepo->getAuditLogsForLeads(array_flip($leadIds), $filters, ['dateAdded', 'DESC'], $params['start']);
        $trackedIds          = [];
        foreach ($historySF['records'] as $sfModifiedDNC) {
            // if we have no history in Mautic, then update the Mautic record
            if (empty($lastModifiedDNCDate)) {
                $leads  = array_flip($leadIds);
                $leadId = $leads[$sfModifiedDNC[$sfObject.'Id']];
                $this->updateMauticDNC($leadId, $sfModifiedDNC['NewValue']);
                $key = $this->getSyncKey($leadEmails[$leadId]);
                unset($sfRecords[$key]['mauticContactIsContactableByEmail']);
                continue;
            }

            foreach ($lastModifiedDNCDate as $logs) {
                $leadId = $logs['objectId'];
                if (strtotime($logs['dateAdded']->format('c')) > strtotime($sfModifiedDNC['CreatedDate'])) {
                    $trackedIds[] = $leadId;
                }
                if (((isset($leadIds[$leadId]) && $leadIds[$leadId] == $sfModifiedDNC[$sfObject.'Id']))
                    && ((strtotime($sfModifiedDNC['CreatedDate']) > strtotime($logs['dateAdded']->format('c')))) && !in_array($leadId, $trackedIds)) {
                    //SF was updated last so update Mautic record
                    $key = $this->getSyncKey($leadEmails[$leadId]);
                    unset($sfRecords[$key]['mauticContactIsContactableByEmail']);
                    $this->updateMauticDNC($leadId, $sfModifiedDNC['NewValue']);
                    $trackedIds[] = $leadId;
                    break;
                }
            }
        }
    }

    /**
     * @param $leadId
     * @param $newDncValue
     */
    private function updateMauticDNC($leadId, $newDncValue)
    {
        $lead = $this->leadModel->getEntity($leadId);

        if ($newDncValue == true) {
            $this->leadModel->addDncForLead($lead, 'email', 'Set by Salesforce', DoNotContact::MANUAL, true, false, true);
        } elseif ($newDncValue == false) {
            $this->leadModel->removeDncForLead($lead, 'email', true);
        }
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushCompanies($params = [])
    {
        $limit                   = (isset($params['limit'])) ? $params['limit'] : 100;
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $config                  = $this->mergeConfigToFeatureSettings($params);
        $integrationEntityRepo   = $this->getIntegrationEntityRepository();

        if (!isset($config['companyFields'])) {
            return [0, 0, 0, 0];
        }

        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors  = 0;
        $sfObject     = 'Account';

        //all available fields in Salesforce for Account
        $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => [$sfObject]]]);

        //get company fields from Mautic that have been mapped
        $mauticCompanyFieldString = implode(', l.', $config['companyFields']);
        $mauticCompanyFieldString = 'l.'.$mauticCompanyFieldString;

        $fieldKeys          = array_keys($config['companyFields']);
        $fieldsToCreate     = $this->prepareFieldsForSync($config['companyFields'], $fieldKeys, $sfObject);
        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config, $sfObject, 'mautic_company');

        $objectFields['company'] = [
            'update' => !empty($fieldsToUpdateInSf) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf) : [],
            'create' => $fieldsToCreate,
        ];

        list($fields, $string) = $this->getRequiredFieldString(
            $config,
            $availableFields,
            'company'
        );

        $objectFields['company']['required'] = [
            'fields' => $fields,
            'string' => $string,
        ];

        if (empty($objectFields)) {
            return [0, 0, 0, 0];
        }

        $originalLimit = $limit;
        $progress      = false;

        // Get a total number of companies to be updated and/or created for the progress counter
        $totalToUpdate = array_sum(
            $integrationEntityRepo->findLeadsToUpdate(
                'Salesforce',
                'company',
                $mauticCompanyFieldString,
                false,
                $fromDate,
                $toDate,
                $sfObject,
                []
            )
        );
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticCompanyFieldString,
            false,
            $fromDate,
            $toDate,
            'company'
        );

        $totalCount = $totalToProcess = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        $noMoreUpdates = false;

        while ($totalCount > 0) {
            $limit              = $originalLimit;
            $mauticData         = [];
            $checkCompaniesInSF = [];
            $companiesToSync    = [];
            $processedCompanies = [];

            // Process the updates
            if (!$noMoreUpdates) {
                $noMoreUpdates = $this->getMauticRecordsToUpdate(
                    $checkCompaniesInSF,
                    $mauticCompanyFieldString,
                    $sfObject,
                    $limit,
                    $fromDate,
                    $toDate,
                    $totalCount,
                    'company'
                );

                if ($limit) {
                    // Mainly done for test mocking purposes
                    $limit = $this->getSalesforceSyncLimit($checkCompaniesInSF, $limit);
                }
            }

            // If there is still room - grab Mautic companies to create if the Lead object is enabled
            $sfEntityRecords = [];
            if ((null === $limit || $limit > 0) && !empty($mauticCompanyFieldString)) {
                $this->getMauticEntitesToCreate(
                    $checkCompaniesInSF,
                    $mauticCompanyFieldString,
                    $limit,
                    $fromDate,
                    $toDate,
                    $totalCount,
                    $progress
                );
            }

            if ($checkCompaniesInSF) {
                $sfEntityRecords = $this->getSalesforceAccountsByName($checkCompaniesInSF, implode(',', array_keys($config['companyFields'])));

                if (!isset($sfEntityRecords['records'])) {
                    // Something is wrong so throw an exception to prevent creating a bunch of new companies
                    $this->cleanupFromSync(
                        $companiesToSync,
                        json_encode($sfEntityRecords)
                    );
                }
            }

            // We're done
            if (!$checkCompaniesInSF) {
                break;
            }

            if (!empty($sfEntityRecords) and isset($sfEntityRecords['records'])) {
                $this->prepareMauticCompaniesToUpdate(
                    $mauticData,
                    $checkCompaniesInSF,
                    $processedCompanies,
                    $companiesToSync,
                    $objectFields,
                    $sfEntityRecords,
                    $progress
                );
            }

            // Only create left over if Lead object is enabled in integration settings
            if ($checkCompaniesInSF) {
                $this->prepareMauticCompaniesToCreate(
                    $mauticData,
                    $checkCompaniesInSF,
                    $processedCompanies,
                    $objectFields
                );
            }

            // Persist pending changes
            $this->cleanupFromSync($companiesToSync);

            $this->makeCompositeRequest($mauticData, $totalUpdated, $totalCreated, $totalErrors);

            // Stop gap - if 100% let's kill the script
            if ($progress && $progress->getProgressPercent() >= 1) {
                break;
            }
        }

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for pushCompanies');

        // Assume that those not touched are ignored due to not having matching fields, duplicates, etc
        $totalIgnored = $totalToProcess - ($totalUpdated + $totalCreated + $totalErrors);

        if ($totalIgnored < 0) { //this could have been marked as deleted so it was not pushed
            $totalIgnored = $totalIgnored * -1;
        }

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    /**
     * @param      $mauticData
     * @param      $checkEmailsInSF
     * @param      $processedLeads
     * @param      $trackedContacts
     * @param      $leadsToSync
     * @param      $requiredFields
     * @param      $objectFields
     * @param      $mauticLeadFieldString
     * @param      $sfEntityRecords
     * @param null $progress
     */
    protected function prepareMauticCompaniesToUpdate(
        &$mauticData,
        &$checkCompaniesInSF,
        &$processedCompanies,
        &$companiesToSync,
        $objectFields,
        $sfEntityRecords,
        $progress = null
    ) {
        foreach ($sfEntityRecords['records'] as $sfKey => $sfEntityRecord) {
            $syncCompany = false;
            $update      = false;
            $sfObject    = $sfEntityRecord['attributes']['type'];
            if (!isset($sfEntityRecord['Name'])) {
                // This is a record we don't recognize so continue
                return;
            }
            $key = $sfEntityRecord['Id'];

            if (!isset($sfEntityRecord['Id'])) {
                // This is a record we don't recognize so continue
                return;
            }

            $id = $sfEntityRecord['Id'];
            if (isset($checkCompaniesInSF[$key])) {
                $companyData = (isset($processedCompanies[$key])) ? $processedCompanies[$key] : $checkCompaniesInSF[$key];
                $update      = true;
            } else {
                foreach ($checkCompaniesInSF as $mauticKey => $mauticCompanies) {
                    $key = $mauticKey;

                    if (isset($mauticCompanies['companyname']) && $mauticCompanies['companyname'] == $sfEntityRecord['Name']) {
                        $companyData = (isset($processedCompanies[$key])) ? $processedCompanies[$key] : $checkCompaniesInSF[$key];
                        $companyId   = $companyData['internal_entity_id'];

                        $integrationEntity = $this->createIntegrationEntity(
                            $sfObject,
                            $id,
                            'company',
                            $companyId
                        );

                        $checkCompaniesInSF[$key]['integration_entity']    = $sfObject;
                        $checkCompaniesInSF[$key]['integration_entity_id'] = $id;
                        $checkCompaniesInSF[$key]['id']                    = $integrationEntity->getId();
                        $update                                            = true;
                    }
                }
            }

            if (!$update) {
                return;
            }

            if (!isset($processedCompanies[$key])) {
                if ($progress) {
                    $progress->advance();
                }
                // Mark that this lead has been processed
                $companyData = $processedCompanies[$key] = $checkCompaniesInSF[$key];
            }

            $companyEntity = $this->em->getReference('MauticLeadBundle:Company', $companyData['internal_entity_id']);

            if ($updateCompany = $this->buildCompositeBody(
                $mauticData,
                $objectFields['company'],
                $sfObject,
                $companyData,
                $sfEntityRecord['Id'],
                $sfEntityRecord
            )
            ) {
                // Get the company entity
                /* @var Lead $leadEntity */
                foreach ($updateCompany as $mauticField => $sfValue) {
                    $companyEntity->addUpdatedField($mauticField, $sfValue);
                }

                $syncCompany = !empty($companyEntity->getChanges(true));
            }
            if ($syncCompany) {
                $companiesToSync[] = $companyEntity;
            } else {
                $this->em->detach($companyEntity);
            }

            unset($checkCompaniesInSF[$key]);
        }
    }

    /**
     * @param $mauticData
     * @param $checkIdsInSF
     * @param $processedCompanies
     * @param $objectFields
     */
    protected function prepareMauticCompaniesToCreate(
        &$mauticData,
        &$checkCompaniesInSF,
        &$processedCompanies,
        $objectFields
    ) {
        foreach ($checkCompaniesInSF as $key => $company) {
            if (!empty($company['integration_entity_id']) and array_key_exists($key, $processedCompanies)) {
                if ($this->buildCompositeBody(
                    $mauticData,
                    $objectFields['company'],
                    $company['integration_entity'],
                    $company,
                    $company['integration_entity_id']
                )
                ) {
                    $this->logger->debug('SALESFORCE: Company has existing ID so updating '.$company['integration_entity_id']);
                }
            } else {
                $this->buildCompositeBody(
                    $mauticData,
                    $objectFields['company'],
                    'Account',
                    $company
                );
            }

            $processedCompanies[$key] = $checkCompaniesInSF[$key];
            unset($checkCompaniesInSF[$key]);
        }
    }

    /**
     * @param $checkEmailsInSF
     * @param $mauticLeadFieldString
     * @param $sfObject
     * @param $trackedContacts
     * @param $limit
     * @param $fromDate
     * @param $toDate
     * @param $totalCount
     *
     * @return bool
     */
    protected function getMauticRecordsToUpdate(
        &$checkIdsInSF,
        $mauticEntityFieldString,
        &$sfObject,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount,
        $internalEntity
    ) {
        // Fetch them separately so we can determine if Leads are already Contacts
        $toUpdate = $this->getIntegrationEntityRepository()->findLeadsToUpdate(
            'Salesforce',
            $internalEntity,
            $mauticEntityFieldString,
            $limit,
            $fromDate,
            $toDate,
            $sfObject
        )[$sfObject];

        $toUpdateCount = count($toUpdate);
        $totalCount -= $toUpdateCount;

        foreach ($toUpdate as $entity) {
            if (!empty($entity['integration_entity_id'])) {
                $checkIdsInSF[$entity['integration_entity_id']] = $entity;
            }
        }

        return 0 === $toUpdateCount;
    }

    /**
     * @param      $checkIdsInSF
     * @param      $mauticCompanyFieldString
     * @param      $limit
     * @param      $fromDate
     * @param      $toDate
     * @param      $totalCount
     * @param null $progress
     */
    protected function getMauticEntitesToCreate(
        &$checkIdsInSF,
        $mauticCompanyFieldString,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount,
        $progress = null
    ) {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $entitiesToCreate      = $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticCompanyFieldString,
            $limit,
            $fromDate,
            $toDate,
            'company'
        );
        $totalCount -= count($entitiesToCreate);

        foreach ($entitiesToCreate as $entity) {
            if (isset($entity['companyname'])) {
                $checkIdsInSF[$entity['internal_entity_id']] = $entity;
            } elseif ($progress) {
                $progress->advance();
            }
        }
    }

    /**
     * @param $checkIdsInSF
     * @param $requiredFieldString
     *
     * @return array
     *
     * @throws ApiErrorException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    protected function getSalesforceAccountsByName(&$checkIdsInSF, $requiredFieldString)
    {
        $searchForIds   = [];
        $searchForNames = [];

        foreach ($checkIdsInSF as $key => $company) {
            if (!empty($company['integration_entity_id'])) {
                $searchForIds[$key] = $company['integration_entity_id'];

                continue;
            }

            if (!empty($company['companyname'])) {
                $searchForNames[$key] = $company['companyname'];
            }
        }

        $resultsByName = $this->getApiHelper()->getCompaniesByName($searchForNames, $requiredFieldString);
        $resultsById   = [];
        if (!empty($searchForIds)) {
            $resultsById = $this->getApiHelper()->getCompaniesById($searchForIds, $requiredFieldString);

            //mark as deleleted
            foreach ($resultsById['records'] as $sfId => $record) {
                if (isset($record['IsDeleted']) && $record['IsDeleted'] == 1) {
                    if ($foundKey = array_search($record['Id'], $searchForIds)) {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $checkIdsInSF[$foundKey]['id']);
                        $integrationEntity->setInternalEntity('company-deleted');
                        $this->persistIntegrationEntities[] = $integrationEntity;
                        unset($checkIdsInSF[$foundKey]);
                    }

                    unset($resultsById['records'][$sfId]);
                }
            }
        }

        $this->cleanupFromSync();
        $result = array_merge($resultsByName, $resultsById);

        return $result;
    }

    public function getCompanyName($accountId, $field, $searchBy = 'Id')
    {
        $companyField   = null;
        $accountId      = str_replace("'", "\'", $this->cleanPushData($accountId));
        $companyQuery   = 'Select Id, Name from Account where '.$searchBy.' = \''.$accountId.'\' and IsDeleted = false';
        $contactCompany = $this->getApiHelper()->getLeads($companyQuery, 'Account');

        if (!empty($contactCompany['records'])) {
            foreach ($contactCompany['records'] as $company) {
                if (!empty($company[$field])) {
                    $companyField = $company[$field];
                    break;
                }
            }
        }

        return $companyField;
    }

    public function getLeadDoNotContactByDate($channel, $matchedFields, $object, $lead, $sfData, $params = [])
    {
        if (isset($matchedFields['mauticContactIsContactableByEmail']) and $this->updateDncByDate() === true) {
            $matchedFields['internal_entity_id']    = $lead->getId();
            $matchedFields['integration_entity_id'] = $sfData['Id__'.$object];
            $record[$lead->getEmail()]              = $matchedFields;
            $this->pushLeadDoNotContactByDate($channel, $record, $object, $params);

            return $record[$lead->getEmail()];
        }

        return $matchedFields;
    }
}
