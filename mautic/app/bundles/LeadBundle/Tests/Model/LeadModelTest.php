<?php

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\IpAddressModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\LegacyLeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadModelTest extends \PHPUnit_Framework_TestCase
{
    private $requestStackMock;
    private $cookieHelperMock;
    private $ipLookupHelperMock;
    private $pathsHelperMock;
    private $integrationHelperkMock;
    private $fieldModelMock;
    private $listModelMock;
    private $formFactoryMock;
    private $companyModelMock;
    private $categoryModelMock;
    private $channelListHelperMock;
    private $coreParametersHelperMock;
    private $emailValidatorMock;
    private $userProviderMock;
    private $contactTrackerMock;
    private $deviceTrackerMock;
    private $legacyLeadModelMock;
    private $ipAddressModelMock;
    private $leadRepositoryMock;
    private $companyLeadRepositoryMock;
    private $userHelperMock;
    private $dispatcherMock;
    private $entityManagerMock;
    private $leadModel;

    protected function setUp()
    {
        parent::setUp();

        $this->requestStackMock          = $this->createMock(RequestStack::class);
        $this->cookieHelperMock          = $this->createMock(CookieHelper::class);
        $this->ipLookupHelperMock        = $this->createMock(IpLookupHelper::class);
        $this->pathsHelperMock           = $this->createMock(PathsHelper::class);
        $this->integrationHelperkMock    = $this->createMock(IntegrationHelper::class);
        $this->fieldModelMock            = $this->createMock(FieldModel::class);
        $this->listModelMock             = $this->createMock(ListModel::class);
        $this->formFactoryMock           = $this->createMock(FormFactory::class);
        $this->companyModelMock          = $this->createMock(CompanyModel::class);
        $this->categoryModelMock         = $this->createMock(CategoryModel::class);
        $this->channelListHelperMock     = $this->createMock(ChannelListHelper::class);
        $this->coreParametersHelperMock  = $this->createMock(CoreParametersHelper::class);
        $this->emailValidatorMock        = $this->createMock(EmailValidator::class);
        $this->userProviderMock          = $this->createMock(UserProvider::class);
        $this->contactTrackerMock        = $this->createMock(ContactTracker::class);
        $this->deviceTrackerMock         = $this->createMock(DeviceTracker::class);
        $this->legacyLeadModelMock       = $this->createMock(LegacyLeadModel::class);
        $this->ipAddressModelMock        = $this->createMock(IpAddressModel::class);
        $this->leadRepositoryMock        = $this->createMock(LeadRepository::class);
        $this->companyLeadRepositoryMock = $this->createMock(CompanyLeadRepository::class);
        $this->userHelperMock            = $this->createMock(UserHelper::class);
        $this->dispatcherMock            = $this->createMock(EventDispatcherInterface::class);
        $this->entityManagerMock         = $this->createMock(EntityManager::class);
        $this->leadModel                 = new LeadModel(
            $this->requestStackMock,
            $this->cookieHelperMock,
            $this->ipLookupHelperMock,
            $this->pathsHelperMock,
            $this->integrationHelperkMock,
            $this->fieldModelMock,
            $this->listModelMock,
            $this->formFactoryMock,
            $this->companyModelMock,
            $this->categoryModelMock,
            $this->channelListHelperMock,
            $this->coreParametersHelperMock,
            $this->emailValidatorMock,
            $this->userProviderMock,
            $this->contactTrackerMock,
            $this->deviceTrackerMock,
            $this->legacyLeadModelMock,
            $this->ipAddressModelMock
        );

        $this->leadModel->setUserHelper($this->userHelperMock);
        $this->leadModel->setDispatcher($this->dispatcherMock);
        $this->leadModel->setEntityManager($this->entityManagerMock);

        $this->entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:Lead', $this->leadRepositoryMock],
                    ]
                )
            );

        $this->companyModelMock->method('getCompanyLeadRepository')->willReturn($this->companyLeadRepositoryMock);
    }

    public function testIpLookupDoesNotAddCompanyIfConfiguredSo()
    {
        $entity    = new Lead();
        $ipAddress = new IpAddress();

        $ipAddress->setIpDetails(['organization' => 'Doctors Without Borders']);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->once())->method('getParameter')->with('ip_lookup_create_organization', false)->willReturn(false);
        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->expects($this->never())->method('getEntitiesByLead');
        $this->companyModelMock->expects($this->never())->method('getEntities');

        $this->leadModel->saveEntity($entity);

        $this->assertNull($entity->getCompany());
        $this->assertTrue(empty($entity->getUpdatedFields()['company']));
    }

    public function testIpLookupAddsCompanyIfDoesNotExistInEntity()
    {
        $companyFromIpLookup = 'Doctors Without Borders';
        $entity              = new Lead();
        $ipAddress           = new IpAddress();

        $ipAddress->setIpDetails(['organization' => $companyFromIpLookup]);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->once())->method('getParameter')->with('ip_lookup_create_organization', false)->willReturn(true);
        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->method('getEntitiesByLead')->willReturn([]);
        $this->companyModelMock->expects($this->once())->method('getEntities')->willReturn([]);

        $this->leadModel->saveEntity($entity);

        $this->assertSame($companyFromIpLookup, $entity->getCompany());
        $this->assertSame($companyFromIpLookup, $entity->getUpdatedFields()['company']);
    }

    public function testIpLookupAddsCompanyIfExistsInEntity()
    {
        $companyFromIpLookup = 'Doctors Without Borders';
        $companyFromEntity   = 'Red Cross';
        $entity              = new Lead();
        $ipAddress           = new IpAddress();

        $entity->setCompany($companyFromEntity);
        $ipAddress->setIpDetails(['organization' => $companyFromIpLookup]);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->never())->method('getParameter');
        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->method('getEntitiesByLead')->willReturn([]);

        $this->leadModel->saveEntity($entity);

        $this->assertSame($companyFromEntity, $entity->getCompany());
        $this->assertFalse(isset($entity->getUpdatedFields()['company']));
    }

    public function testCheckForDuplicateContact()
    {
        $this->fieldModelMock->expects($this->at(0))
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn(['email' => 'Email', 'firstname' => 'First Name']);

        $this->fieldModelMock->expects($this->at(1))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                4 => ['label' => 'Email', 'alias' => 'email', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'email'],
                5 => ['label' => 'First Name', 'alias' => 'firstname', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
            ]);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        $contact = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John']);
        $this->assertAttributeEquals(['email' => 'Email', 'firstname' => 'First Name'], 'availableLeadFields', $mockLeadModel);
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertEquals('John', $contact->getFirstname());
    }

    public function testCheckForDuplicateContactForOnlyPubliclyUpdatable()
    {
        $this->fieldModelMock->expects($this->at(0))
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead', 'isPubliclyUpdatable' => true])
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->at(1))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                4 => ['label' => 'Email', 'alias' => 'email', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'email'],
                5 => ['label' => 'First Name', 'alias' => 'firstname', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
            ]);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        list($contact, $fields) = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John'], null, true, true);
        $this->assertAttributeEquals(['email' => 'Email'], 'availableLeadFields', $mockLeadModel);
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertNull($contact->getFirstname());
        $this->assertEquals(['email' => 'john@doe.com'], $fields);
    }

    /**
     * Test that the Lead won't be set to the LeadEventLog if the Lead save fails.
     */
    public function testImportWillNotSetLeadToLeadEventLogWhenLeadSaveFails()
    {
        $leadEventLog  = new LeadEventLog();
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel->setUserHelper($mockUserModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('saveEntity')->willThrowException(new \Exception());
        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn(new Lead());

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception $e) {
            $this->assertNull($leadEventLog->getLead());
        }
    }

    /**
     * Test that the Lead will be set to the LeadEventLog if the Lead save succeed.
     */
    public function testImportWillSetLeadToLeadEventLogWhenLeadSaveSucceed()
    {
        $leadEventLog  = new LeadEventLog();
        $lead          = new Lead();
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel->setUserHelper($mockUserModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn($lead);

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception $e) {
            $this->assertEquals($lead, $leadEventLog->getLead());
        }
    }

    /**
     * Set protected property to an object.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed  $value
     */
    private function setProperty($object, $class, $property, $value)
    {
        $reflectedProp = new \ReflectionProperty($class, $property);
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($object, $value);
    }
}
