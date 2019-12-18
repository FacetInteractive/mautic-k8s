<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\Salesforce\Exception\RetryRequestException;
use MauticPlugin\MauticCrmBundle\Api\Salesforce\Helper\RequestUrl;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

/**
 * @property SalesforceIntegration $integration
 */
class SalesforceApi extends CrmApi
{
    protected $object          = 'Lead';
    protected $requestSettings = [
        'encode_parameters' => 'json',
    ];
    protected $apiRequestCounter   = 0;
    protected $requestCounter      = 1;
    protected $maxLockRetries      = 3;

    public function __construct(CrmAbstractIntegration $integration)
    {
        parent::__construct($integration);

        $this->requestSettings['curl_options'] = [
            CURLOPT_SSLVERSION => defined('CURL_SSLVERSION_TLSv1_1') ? CURL_SSLVERSION_TLSv1_1 : 5,
        ];
    }

    /**
     * @param        $operation
     * @param array  $elementData
     * @param string $method
     * @param bool   $isRetry
     * @param null   $object
     * @param null   $queryUrl
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, $elementData = [], $method = 'GET', $isRetry = false, $object = null, $queryUrl = null)
    {
        if (!$object) {
            $object = $this->object;
        }

        $requestUrl = RequestUrl::get($this->integration->getApiUrl(), $queryUrl, $operation, $object);

        $settings   = $this->requestSettings;
        if ($method == 'PATCH') {
            $settings['headers'] = ['Sforce-Auto-Assign' => 'FALSE'];
        }

        // Query commands can have long wait time while SF builds response as the offset increases
        $settings['request_timeout'] = 300;

        // Wrap in a isAuthorized to refresh token if applicable
        $response = $this->integration->makeRequest($requestUrl, $elementData, $method, $settings);
        ++$this->apiRequestCounter;

        try {
            $this->analyzeResponse($response, $isRetry);
        } catch (RetryRequestException $exception) {
            return $this->request($operation, $elementData, $method, true, $object, $queryUrl);
        }

        return $response;
    }

    /**
     * @param null $object
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeadFields($object = null)
    {
        if ($object == 'company') {
            $object = 'Account'; //salesforce object name
        }

        return $this->request('describe', [], 'GET', false, $object);
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getPerson(array $data)
    {
        $config    = $this->integration->mergeConfigToFeatureSettings([]);
        $queryUrl  = $this->integration->getQueryUrl();
        $sfRecords = [
            'Contact' => [],
            'Lead'    => [],
        ];

        //try searching for lead as this has been changed before in updated done to the plugin
        if (isset($config['objects']) && false !== array_search('Contact', $config['objects']) && !empty($data['Contact']['Email'])) {
            $fields      = $this->integration->getFieldsForQuery('Contact');
            $fields[]    = 'Id';
            $fields      = implode(', ', array_unique($fields));
            $findContact = 'select '.$fields.' from Contact where email = \''.$this->escapeQueryValue($data['Contact']['Email']).'\'';
            $response    = $this->request('query', ['q' => $findContact], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Contact'] = $response['records'];
            }
        }

        if (!empty($data['Lead']['Email'])) {
            $fields   = $this->integration->getFieldsForQuery('Lead');
            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));
            $findLead = 'select '.$fields.' from Lead where email = \''.$this->escapeQueryValue($data['Lead']['Email']).'\' and ConvertedContactId = NULL';
            $response = $this->request('queryAll', ['q' => $findLead], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Lead'] = $response['records'];
            }
        }

        return $sfRecords;
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getCompany(array $data)
    {
        $config    = $this->integration->mergeConfigToFeatureSettings([]);
        $queryUrl  = $this->integration->getQueryUrl();
        $sfRecords = [
            'Account' => [],
        ];

        $appendToQuery = '';

        //try searching for lead as this has been changed before in updated done to the plugin
        if (isset($config['objects']) && false !== array_search('company', $config['objects']) && !empty($data['company']['Name'])) {
            $fields = $this->integration->getFieldsForQuery('Account');

            if (!empty($data['company']['BillingCountry'])) {
                $appendToQuery .= ' and BillingCountry =  \''.$this->escapeQueryValue($data['company']['BillingCountry']).'\'';
            }
            if (!empty($data['company']['BillingCity'])) {
                $appendToQuery .= ' and BillingCity =  \''.$this->escapeQueryValue($data['company']['BillingCity']).'\'';
            }
            if (!empty($data['company']['BillingState'])) {
                $appendToQuery .= ' and BillingState =  \''.$this->escapeQueryValue($data['company']['BillingState']).'\'';
            }

            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));
            $query    = 'select '.$fields.' from Account where Name = \''.$this->escapeQueryValue($data['company']['Name']).'\''.$appendToQuery;
            $response = $this->request('queryAll', ['q' => $query], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['company'] = $response['records'];
            }
        }

        return $sfRecords;
    }

    /**
     * @param array $data
     *
     * @return array|mixed|string
     *
     * @throws ApiErrorException
     */
    public function createLead(array $data)
    {
        $createdLeadData = [];

        if (isset($data['Email'])) {
            $createdLeadData = $this->createObject($data, 'Lead');
        }

        return $createdLeadData;
    }

    /**
     * @param array $data
     * @param       $sfObject
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function createObject(array $data, $sfObject)
    {
        $objectData = $this->request('', $data, 'POST', false, $sfObject);
        $this->integration->getLogger()->debug('SALESFORCE: POST createObject '.$sfObject.' '.var_export($data, true).var_export($objectData, true));

        if (isset($objectData['id'])) {
            // Salesforce is inconsistent it seems
            $objectData['Id'] = $objectData['id'];
        }

        return $objectData;
    }

    /**
     * @param array $data
     * @param       $sfObject
     * @param       $sfObjectId
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function updateObject(array $data, $sfObject, $sfObjectId)
    {
        $objectData = $this->request('', $data, 'PATCH', false, $sfObject.'/'.$sfObjectId);
        $this->integration->getLogger()->debug('SALESFORCE: PATCH updateObject '.$sfObject.' '.var_export($data, true).var_export($objectData, true));

        // Salesforce is inconsistent it seems
        $objectData['Id'] = $objectData['id'] = $sfObjectId;

        return $objectData;
    }

    /**
     * @param array $data
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function syncMauticToSalesforce(array $data)
    {
        $queryUrl = $this->integration->getCompositeUrl();

        return $this->request('composite/', $data, 'POST', false, null, $queryUrl);
    }

    /**
     * @param array $activity
     * @param       $object
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function createLeadActivity(array $activity, $object)
    {
        $config              = $this->integration->getIntegrationSettings()->getFeatureSettings();
        $namespace           = (!empty($config['namespace'])) ? $config['namespace'].'__' : '';
        $mActivityObjectName = $namespace.'mautic_timeline__c';
        $activityData        = [];

        if (!empty($activity)) {
            foreach ($activity as $sfId => $records) {
                foreach ($records['records'] as $record) {
                    $body = [
                        $namespace.'ActivityDate__c' => $record['dateAdded']->format('c'),
                        $namespace.'Description__c'  => $record['description'],
                        'Name'                       => $record['name'],
                        $namespace.'Mautic_url__c'   => $records['leadUrl'],
                        $namespace.'ReferenceId__c'  => $record['id'].'-'.$sfId,
                    ];

                    if ($object === 'Lead') {
                        $body[$namespace.'WhoId__c'] = $sfId;
                    } elseif ($object === 'Contact') {
                        $body[$namespace.'contact_id__c'] = $sfId;
                    }

                    $activityData[] = [
                        'method'      => 'POST',
                        'url'         => '/services/data/v38.0/sobjects/'.$mActivityObjectName,
                        'referenceId' => $record['id'].'-'.$sfId,
                        'body'        => $body,
                    ];
                }
            }

            if (!empty($activityData)) {
                $request              = [];
                $request['allOrNone'] = 'false';
                $chunked              = array_chunk($activityData, 25);
                $results              = [];
                foreach ($chunked as $chunk) {
                    // We can only submit 25 at a time
                    if ($chunk) {
                        $request['compositeRequest'] = $chunk;
                        $result                      = $this->syncMauticToSalesforce($request);
                        $results[]                   = $result;
                        $this->integration->getLogger()->debug('SALESFORCE: Activity response '.var_export($result, true));
                    }
                }

                return $results;
            }

            return [];
        }
    }

    /**
     * Get Salesforce leads.
     *
     * @param mixed  $query  String for a SOQL query or array to build query
     * @param string $object
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeads($query, $object)
    {
        $queryUrl = $this->integration->getQueryUrl();

        if (defined('MAUTIC_ENV') && MAUTIC_ENV === 'dev') {
            // Easier for testing
            $this->requestSettings['headers']['Sforce-Query-Options'] = 'batchSize=200';
        }

        if (!is_array($query)) {
            return $this->request('queryAll', ['q' => $query], 'GET', false, null, $queryUrl);
        }

        if (!empty($query['nextUrl'])) {
            return $this->request(null, [], 'GET', false, null, $query['nextUrl']);
        }

        $organizationCreatedDate = $this->getOrganizationCreatedDate();
        $fields                  = $this->integration->getFieldsForQuery($object);
        if (!empty($fields) && isset($query['start'])) {
            if (strtotime($query['start']) < strtotime($organizationCreatedDate)) {
                $query['start'] = date('c', strtotime($organizationCreatedDate.' +1 hour'));
            }

            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));

            $config = $this->integration->mergeConfigToFeatureSettings([]);
            if (isset($config['updateOwner']) && isset($config['updateOwner'][0]) && $config['updateOwner'][0] == 'updateOwner') {
                $fields = 'Owner.Name, Owner.Email, '.$fields;
            }

            $ignoreConvertedLeads = ($object == 'Lead') ? ' and ConvertedContactId = NULL' : '';

            $getLeadsQuery = 'SELECT '.$fields.' from '.$object.' where SystemModStamp>='.$query['start'].' and SystemModStamp<='.$query['end']
                .$ignoreConvertedLeads;

            return $this->request('queryAll', ['q' => $getLeadsQuery], 'GET', false, null, $queryUrl);
        }

        return [
            'totalSize' => 0,
            'records'   => [],
        ];
    }

    /**
     * @return bool|mixed
     *
     * @throws ApiErrorException
     */
    public function getOrganizationCreatedDate()
    {
        $cache = $this->integration->getCache();

        if (!$organizationCreatedDate = $cache->get('organization.created_date')) {
            $queryUrl                = $this->integration->getQueryUrl();
            $organization            = $this->request('query', ['q' => 'SELECT CreatedDate from Organization'], 'GET', false, null, $queryUrl);
            $organizationCreatedDate = $organization['records'][0]['CreatedDate'];
            $cache->set('organization.created_date', $organizationCreatedDate);
        }

        return $organizationCreatedDate;
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaigns()
    {
        $campaignQuery = 'Select Id, Name from Campaign where isDeleted = false';
        $queryUrl      = $this->integration->getQueryUrl();

        $result = $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);

        return $result;
    }

    /**
     * @param      $campaignId
     * @param null $modifiedSince
     * @param null $queryUrl
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMembers($campaignId, $modifiedSince = null, $queryUrl = null)
    {
        $defaultSettings = $this->requestSettings;

        // Control batch size to prevent URL too long errors when fetching contact details via SOQL and to control Doctrine RAM usage for
        // Mautic IntegrationEntity objects
        $this->requestSettings['headers']['Sforce-Query-Options'] = 'batchSize=200';

        if (null === $queryUrl) {
            $queryUrl = $this->integration->getQueryUrl().'/query';
        }

        $query = "Select CampaignId, ContactId, LeadId, isDeleted from CampaignMember where CampaignId = '".trim($campaignId)."'";
        if ($modifiedSince) {
            $query .= ' and SystemModStamp >= '.$modifiedSince;
        }

        $results = $this->request(null, ['q' => $query], 'GET', false, null, $queryUrl);

        $this->requestSettings = $defaultSettings;

        return $results;
    }

    /**
     * @param       $campaignId
     * @param       $object
     * @param array $people
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function checkCampaignMembership($campaignId, $object, array $people)
    {
        $campaignMembers = [];
        if (!empty($people)) {
            $idField = "{$object}Id";
            $query   = "Select Id, $idField from CampaignMember where CampaignId = '".$campaignId
                ."' and $idField in ('".implode("','", $people)."')";

            $foundCampaignMembers = $this->request('query', ['q' => $query], 'GET', false, null, $this->integration->getQueryUrl());
            if (!empty($foundCampaignMembers['records'])) {
                foreach ($foundCampaignMembers['records'] as $member) {
                    $campaignMembers[$member[$idField]] = $member['Id'];
                }
            }
        }

        return $campaignMembers;
    }

    /**
     * @param $campaignId
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMemberStatus($campaignId)
    {
        $campaignQuery = "Select Id, Label from CampaignMemberStatus where isDeleted = false and CampaignId='".$campaignId."'";
        $queryUrl      = $this->integration->getQueryUrl();

        $result = $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);

        return $result;
    }

    /**
     * @return int
     */
    public function getRequestCounter()
    {
        $count                   = $this->apiRequestCounter;
        $this->apiRequestCounter = 0;

        return $count;
    }

    /**
     * @param array $names
     * @param null  $requiredFieldString
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCompaniesByName(array $names, $requiredFieldString)
    {
        $names     = array_map([$this, 'escapeQueryValue'], $names);
        $queryUrl  = $this->integration->getQueryUrl();
        $findQuery = 'select Id, '.$requiredFieldString.' from Account where isDeleted = false and Name in (\''.implode("','", $names).'\')';

        return $this->request('query', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param array $ids
     * @param       $requiredFieldString
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCompaniesById(array $ids, $requiredFieldString)
    {
        $findQuery = 'select isDeleted, Id, '.$requiredFieldString.' from Account where  Id in (\''.implode("','", $ids).'\')';
        $queryUrl  = $this->integration->getQueryUrl();

        return $this->request('queryAll', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param mixed $response
     * @param bool  $isRetry
     *
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function analyzeResponse($response, $isRetry)
    {
        if (is_array($response)) {
            if (!empty($response['errors'])) {
                throw new ApiErrorException(implode(', ', $response['errors']));
            }

            foreach ($response as $lineItem) {
                if (is_array($lineItem) && !empty($lineItem['errorCode']) && $error = $this->processError($lineItem, $isRetry)) {
                    $errors[] = $error;
                }
            }

            if (!empty($errors)) {
                throw new ApiErrorException(implode(', ', $errors));
            }
        }
    }

    /**
     * @param array $error
     * @param       $isRetry
     *
     * @return string|false
     *
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function processError(array $error, $isRetry)
    {
        switch ($error['errorCode']) {
            case 'INVALID_SESSION_ID':
                $this->revalidateSession($isRetry);
                break;
            case 'UNABLE_TO_LOCK_ROW':
                $this->checkIfLockedRequestShouldBeRetried();
                break;
        }

        if (!empty($error['message'])) {
            return $error['message'];
        }

        return false;
    }

    /**
     * @param $isRetry
     *
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function revalidateSession($isRetry)
    {
        if ($refreshError = $this->integration->authCallback(['use_refresh_token' => true])) {
            throw new ApiErrorException($refreshError);
        }

        if (!$isRetry) {
            throw new RetryRequestException();
        }
    }

    /**
     * @throws RetryRequestException
     */
    private function checkIfLockedRequestShouldBeRetried()
    {
        // The record is locked so let's wait a a few seconds and retry
        if ($this->requestCounter < $this->maxLockRetries) {
            sleep($this->requestCounter * 3);
            ++$this->requestCounter;

            throw new RetryRequestException();
        }

        $this->requestCounter = 1;

        return false;
    }

    /**
     * @param $value
     *
     * @return bool|float|mixed|string
     */
    private function escapeQueryValue($value)
    {
        // SF uses backslashes as escape delimeter
        // Remember that PHP uses \ as an escape. Therefore, to replace a single backslash with 2, must use 2 and 4
        $value = str_replace('\\', '\\\\', $value);

        // Escape single quotes
        $value = str_replace("'", "\'", $value);

        // Apply general formatting/cleanup
        $value = $this->integration->cleanPushData($value);

        return $value;
    }
}
