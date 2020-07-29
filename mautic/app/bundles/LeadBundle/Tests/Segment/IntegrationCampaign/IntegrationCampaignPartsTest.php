<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\IntegrationCampaign;

use Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts;

class IntegrationCampaignPartsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getIntegrationName()
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getCampaignId()
     */
    public function testConnectwise()
    {
        $field             = 'Connectwise::283';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Connectwise', $doNotContactParts->getIntegrationName());
        $this->assertSame('283', $doNotContactParts->getCampaignId());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getIntegrationName()
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getCampaignId()
     */
    public function testSalesforceExplicit()
    {
        $field             = 'Salesforce::22';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Salesforce', $doNotContactParts->getIntegrationName());
        $this->assertSame('22', $doNotContactParts->getCampaignId());
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getIntegrationName()
     * @covers \Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts::getCampaignId()
     */
    public function testSalesforceDefault()
    {
        $field             = '44';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Salesforce', $doNotContactParts->getIntegrationName());
        $this->assertSame('44', $doNotContactParts->getCampaignId());
    }
}
