<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Mautic\LeadBundle\Entity\Company;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CompanyExport extends AbstractPipedrive
{
    public function pushCompany(Company $company)
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        $mappedData        = $this->getMappedCompanyData($company);
        $integrationEntity = $this->getCompanyIntegrationEntity(['internalEntityId' => $company->getId()]);

        if ($integrationEntity) {
            return $this->update($integrationEntity, $mappedData);
        }

        return $this->create($company, $mappedData);
    }

    public function create(Company $company, array $mappedData = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        $companyId = $company->getId();

        $integrationEntity = $this->getCompanyIntegrationEntity(['internalEntityId' => $companyId]);

        if ($integrationEntity) {
            return false; // company has integration
        }

        try {
            $createdData       = $this->getIntegration()->getApiHelper()->createCompany($mappedData);
            $integrationEntity = $this->createIntegrationCompanyEntity(new \DateTime(), $createdData['id'], $companyId);

            $this->em->persist($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    public function update(IntegrationEntity $integrationEntity, array $mappedData = [])
    {
        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            return false; //feature disabled
        }

        try {
            $this->getIntegration()->getApiHelper()->updateCompany($mappedData, $integrationEntity->getIntegrationEntityId());
            $integrationEntity->setLastSyncDate(new \DateTime());

            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    public function delete(Company $company)
    {
        $integrationEntity = $this->getCompanyIntegrationEntity(['internalEntityId' => $company->getId()]);

        if (!$integrationEntity) {
            return true; // company doesn't have integration
        }

        if (!$this->getIntegration()->isCompanySupportEnabled()) {
            //feature disabled
            $this->em->remove($integrationEntity);
            $this->em->flush();

            return false;
        }

        try {
            $this->getIntegration()->getApiHelper()->removeCompany($integrationEntity->getIntegrationEntityId());

            $this->em->remove($integrationEntity);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }

    private function getMappedCompanyData(Company $company)
    {
        $mappedData    = [];
        $companyFields = $this->getIntegration()->getIntegrationSettings()->getFeatureSettings()['companyFields'];

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($companyFields as $externalField => $internalField) {
            $fieldName                  = substr($internalField, strlen($company::FIELD_ALIAS));
            $mappedData[$externalField] = $accessor->getValue($company, $fieldName);
        }

        $companyIntegrationOwnerId = $this->getCompanyIntegrationOwnerId($company);
        if ($companyIntegrationOwnerId) {
            $mappedData['owner_id'] = $companyIntegrationOwnerId;
        }

        return $mappedData;
    }

    private function getCompanyIntegrationOwnerId(Company $company)
    {
        $mauticOwner = $company->getOwner();

        if (!$mauticOwner) {
            return null;
        }

        $pipedriveOwner = $this->em->getRepository(PipedriveOwner::class)->findOneByEmail($mauticOwner->getEmail());

        if (!$pipedriveOwner) {
            return null;
        }

        return $pipedriveOwner->getOwnerId();
    }
}
