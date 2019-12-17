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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\UserBundle\Entity\User;

/**
 * Class CrmAbstractIntegration.
 */
abstract class CrmAbstractIntegration extends AbstractIntegration
{
    protected $auth;
    protected $pushContactLink = false;

    /**
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        //make sure URL does not have ending /
        $keys = $this->getDecryptedApiKeys($settings);
        if (isset($keys['url']) && substr($keys['url'], -1) == '/') {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'rest';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads'];
    }

    /**
     * @param $lead
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->populateLeadData($lead, $config);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $LeadData = $this->getApiHelper()->createLead($mappedData, $lead);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param $lead
     */
    public function getLeads($params = [])
    {
        $executed = null;

        $query = $this->getFetchQuery($params);

        try {
            if ($this->isAuthorized()) {
                $result = $this->getApiHelper()->getLeads($query);

                $executed = $this->amendLeadDataBeforeMauticPopulate($result);

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * Amend mapped lead data before pushing to CRM.
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
    }

    /**
     * get query to fetch lead data.
     *
     * @param $config
     */
    public function getFetchQuery($config)
    {
    }

    /**
     * Amend mapped lead data before creating to Mautic.
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        return null;
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        static $helper;
        if (empty($helper)) {
            $class  = '\\MauticPlugin\\MauticCrmBundle\\Api\\'.$this->getName().'Api';
            $helper = new $class($this);
        }

        return $helper;
    }

    /**
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param                $leadId
     *
     * @return array
     */
    public function getLeadData(\DateTime $startDate = null, \DateTime $endDate = null, $leadId)
    {
        return [];
    }

    /**
     * @param array $params
     */
    public function pushLeadActivity($params = [])
    {
    }

    /**
     * @param $data
     *
     * @return Company|void
     */
    public function getMauticCompany($data)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config = $this->mergeConfigToFeatureSettings([]);
        // Match that data with mapped lead fields
        $matchedFields          = $this->populateMauticLeadData($data, $config, 'company');
        $fieldsToUpdateInMautic = isset($config['update_mautic_company']) ? array_keys($config['update_mautic_company'], 0) : [];
        if (!empty($fieldsToUpdateInMautic)) {
            $fieldsToUpdateInMautic = array_diff_key($config['companyFields'], array_flip($fieldsToUpdateInMautic));
            $newMatchedFields       = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
        } else {
            $newMatchedFields = $matchedFields;
        }
        if (!isset($newMatchedFields['companyname'])) {
            if (isset($newMatchedFields['companywebsite'])) {
                $newMatchedFields['companyname'] = $newMatchedFields['companywebsite'];
            }
        }

        if (empty($newMatchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $companyModel = $this->factory->getModel('lead.company');

        // Default to new company
        $company         = new Company();
        $existingCompany = IdentifyCompanyHelper::identifyLeadsCompany($matchedFields, null, $companyModel);
        if ($existingCompany[2]) {
            $company = $existingCompany[2];
        } else {
            $matchedFields = $newMatchedFields; //change direction of fields only when updating an existing company
        }

        $companyModel->setFieldValues($company, $matchedFields, false, false);
        $companyModel->saveEntity($company, false);

        return $company;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed       $data        Profile data from integration
     * @param bool|true   $persist     Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config = $this->mergeConfigToFeatureSettings([]);
        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data, $config);

        if (empty($matchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->factory->getModel('lead');
        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = [];

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        if (count(array_diff_key($uniqueLeadFields, $matchedFields)) == count($uniqueLeadFields)) {
            //return if uniqueIdentifiers have no data set to avoid duplicating leads.
            return;
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        //use direction of fields only when updating existing lead
        $fieldsToUpdateInMautic = (isset($config['update_mautic']) && empty($existingLeads)) ? array_keys($config['update_mautic'], 0) : [];
        if (!empty($fieldsToUpdateInMautic)) {
            $fieldsToUpdateInMautic = array_diff_key($config['leadFields'], array_flip($fieldsToUpdateInMautic));
            $matchedFields          = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
        }
        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        if (!empty($socialCache)) {
            // Update the social cache
            $leadSocialCache = $lead->getSocialCache();
            if (!isset($leadSocialCache[$this->getName()])) {
                $leadSocialCache[$this->getName()] = [];
            }
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);

            // Check for activity while here
            if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
                $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
            }

            $lead->setSocialCache($leadSocialCache);
        }

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        // Update the owner if it matches (needs to be set by the integration) when fetching the data
        if (isset($data['owner_email']) && isset($config['updateOwner']) && isset($config['updateOwner'][0]) && $config['updateOwner'][0] == 'updateOwner') {
            if ($mauticUser = $this->em->getRepository('MauticUserBundle:User')->findOneBy(['email' => $data['owner_email']])) {
                $lead->setOwner($mauticUser);
            }
        }

        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            $leadModel->saveEntity($lead, false);
        }

        return $lead;
    }

    /**
     * @param $object
     *
     * @return array|mixed
     */
    protected function getFormFieldsByObject($object, $settings = [])
    {
        $settings['feature_settings']['objects'] = [$object => $object];

        $fields = ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];

        return (isset($fields[$object])) ? $fields[$object] : [];
    }
}
