<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class VtigerApi extends CrmApi
{
    protected $element = 'Leads';

    protected function request($operation, $element, $elementData = [], $method = 'GET')
    {
        $tokenData = $this->integration->getKeys();

        $request_url = $this->integration->getApiUrl();
        $parameters  = [
            'operation'   => $operation,
            'sessionName' => $tokenData['sessionName'],
            'elementType' => $element,
        ];

        if (!empty($elementData)) {
            $parameters['element'] = json_encode($elementData);
        }
        $response = $this->integration->makeRequest($request_url, $parameters, $method);

        if (!empty($response['error'])) {
            $error = $response['error']['message'];

            throw new ApiErrorException($error);
        }

        return $response['result'];
    }

    /**
     * List types.
     *
     * @return mixed
     */
    public function listTypes()
    {
        return $this->request('listtypes', $this->element);
    }

    /**
     * List leads.
     *
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->request('describe', $this->element);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data)
    {
        return $this->request('create', $this->element, $data, 'POST');
    }
}
