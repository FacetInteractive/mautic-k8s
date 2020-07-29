<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;

class PrimaryCompanyHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompanyLeadRepository|\PHPUnit_Framework_Exception
     */
    private $leadRepository;

    protected function setUp()
    {
        $this->leadRepository = $this->createMock(CompanyLeadRepository::class);

        $this->leadRepository->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->willReturn(
                [
                    [
                        'score'           => 0,
                        'date_added'      => '2018-06-02 00:00:00',
                        'date_associated' => '2018-06-02 00:00:00',
                        'is_primary'      => 1,
                        'companywebsite'  => 'https://foo.com',
                    ],
                    [
                        'score'           => 0,
                        'date_added'      => '2018-06-02 00:00:00',
                        'date_associated' => '2018-06-02 00:00:00',
                        'is_primary'      => 0,
                        'companywebsite'  => 'https://bar.com',
                    ],
                ]
            );
    }

    public function testProfileFieldsReturnedWithPrimaryCompany()
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'email' => 'test@test.com',
                ]
            );

        $profileFields = $this->getPrimaryCompanyHelper()->getProfileFieldsWithPrimaryCompany($lead);

        $this->assertEquals(['email' => 'test@test.com', 'companywebsite' => 'https://foo.com'], $profileFields);
    }

    public function testPrimaryCompanyMergedIntoProfileFields()
    {
        $leadFields = [
            'email' => 'test@test.com',
        ];

        $profileFields = $this->getPrimaryCompanyHelper()->mergePrimaryCompanyWithProfileFields(1, $leadFields);

        $this->assertEquals(['email' => 'test@test.com', 'companywebsite' => 'https://foo.com'], $profileFields);
    }

    /**
     * @return PrimaryCompanyHelper
     */
    private function getPrimaryCompanyHelper()
    {
        return new PrimaryCompanyHelper($this->leadRepository);
    }
}
