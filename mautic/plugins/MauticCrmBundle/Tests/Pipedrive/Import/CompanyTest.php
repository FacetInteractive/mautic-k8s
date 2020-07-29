<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class CompanyTest extends PipedriveTest
{
    private $features = [
        'objects' => [
            'company',
        ],
        'companyFields' => [
            'name'    => 'companyname',
            'address' => 'companyaddress1',
        ],
    ];

    public function testCreateCompanyViaUpdate()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );
        $data = $this->getData('organization.updated');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $c            = $this->em->getRepository(Company::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($c->getName(), 'Changed Company Name New');
        $this->assertEquals($c->getAddress1(), 'Madrit, Spain');
        $this->assertEquals(count($this->em->getRepository(Company::class)->findAll()), 1);
    }

    public function testUpdateCompanyOwner()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );

        $json = $this->getData('organization.updated');
        $data = json_decode($json, true);

        $company  = $this->createCompany();
        $newOwner = $this->createUser(true, 'admin@admin.pl');
        $owner    = $this->createUser(true, 'test@test.pl', 'user');

        $this->createCompanyIntegrationEntity($data['current']['id'], $company->getId());
        $this->addOwnerToCompany($owner, $company);
        $this->addPipedriveOwner($data['current']['owner_id'], $newOwner->getEmail());

        $this->assertEquals($company->getOwner()->getEmail(), $owner->getEmail());

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $c            = $this->em->getRepository(Company::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($c->getName(), 'Changed Company Name New');
        $this->assertEquals($c->getAddress1(), 'Madrit, Spain');
        $this->assertEquals($c->getOwner()->getEmail(), $newOwner->getEmail());
        $this->assertEquals(count($this->em->getRepository(Company::class)->findAll()), 1);
    }

    public function testUpdateCompany()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );
        $company = $this->createCompany();
        $json    = $this->getData('organization.updated');
        $data    = json_decode($json, true);
        $this->createCompanyIntegrationEntity($data['current']['id'], $company->getId());

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $c            = $this->em->getRepository(Company::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($c->getName(), 'Changed Company Name New');
        $this->assertEquals($c->getAddress1(), 'Madrit, Spain');
        $this->assertEquals(count($this->em->getRepository(Company::class)->findAll()), 1);
    }

    public function testDeleteCompany()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );
        $company = $this->createCompany();
        $json    = $this->getData('organization.deleted');
        $data    = json_decode($json, true);
        $this->createCompanyIntegrationEntity($data['previous']['id'], $company->getId());

        $this->assertEquals(count($this->em->getRepository(Company::class)->findAll()), 1);

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals(count($this->em->getRepository(Company::class)->findAll()), 0);
    }
}
