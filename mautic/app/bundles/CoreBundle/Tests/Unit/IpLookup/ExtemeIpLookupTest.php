<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\unit\IpLookup;

use Mautic\CoreBundle\IpLookup\ExtremeIpLookup;

/**
 * Class ExtremeIpLookupTest.
 */
class ExtemeIpLookupTest extends \PHPUnit_Framework_TestCase
{
    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->getMockBuilder('Joomla\Http\Http')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock a successful response
        $mockResponse = $this->getMockBuilder('Joomla\Http\Response')
            ->getMock();
        $mockResponse->code = 200;
        $mockResponse->body = '{"businessName" : "Sandhills Publishing Company","businessWebsite" : "www.sandhills.com","city" : "Lincoln","continent" : "North America","country" : "United States","countryCode" : "US","ipName" : "proxy.sandhills.com","ipType" : "Business","isp" : "Sandhills Publishing Company","lat" : "40.8615","lon" : "-96.7119","org" : "Sandhills Publishing Company","query" : "63.70.164.200","region" : "Nebraska","status" : "success"}';

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new ExtremeIpLookup(null, null, __DIR__.'/../../../../cache/test', null, $mockHttp);

        $details = $ipService->setIpAddress('63.70.164.200')->getDetails();

        $this->assertEquals('Lincoln', $details['city']);
        $this->assertEquals('Nebraska', $details['region']);
        $this->assertEquals('United States', $details['country']);
    }
}
