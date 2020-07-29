<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MauticCrmBundle\Tests\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

class SalesforceApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that a locked record request is retried up to 3 times
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::checkIfLockedRequestShouldBeRetried()
     */
    public function testRecordLockedErrorIsRetriedThreeTimes()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';
        $integration->expects($this->exactly(3))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');

            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a locked record request is retried up to 3 times with last one being successful so no exception should be thrown
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::checkIfLockedRequestShouldBeRetried()
     */
    public function testRecordLockedErrorIsRetriedThreeTimesWithLastOneSuccessful()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';
        $integration->expects($this->at(1))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );

        $integration->expects($this->at(2))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );

        $integration->expects($this->at(3))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'success' => true,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException $exception) {
            $this->fail('ApiErrorException thrown');
        }
    }

    /**
     * @testdox Test that a locked record request is retried 2 times with 3rd being successful
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::checkIfLockedRequestShouldBeRetried()
     */
    public function testRecordLockedErrorIsRetriedTwoTimesWithThirdSuccess()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';
        $integration->expects($this->at(1))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );
        $integration->expects($this->at(0))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        ['success' => true],
                    ],
                ]
            );
        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException $exception) {
            $this->fail('ApiErrorException should not have been thrown');
        }
    }

    /**
     * @testdox Test that a session expired should attempt a refresh before failing
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::revalidateSession()
     */
    public function testSessionExpiredIsRefreshed()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integration->expects($this->exactly(2))
            ->method('authCallback');

        $message = 'Session expired';
        $integration->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'INVALID_SESSION_ID',
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a session expired should attempt a refresh but not throw an exception if successful on second request
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::revalidateSession()
     */
    public function testSessionExpiredIsRefreshedWithoutThrowingExceptionOnSecondRequestWithSuccess()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integration->expects($this->once())
            ->method('authCallback');

        $message = 'Session expired';

        // Test again but both attempts should fail resulting in
        $integration->expects($this->at(1))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'INVALID_SESSION_ID',
                        'message'   => $message,
                    ],
                ]
            );

        $integration->expects($this->at(2))
            ->method('makeRequest')
            ->willReturn(
                [
                    ['success' => true],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException $exception) {
            $this->fail('ApiErrorException thrown');
        }
    }

    /**
     * @testdox Test that an exception is thrown for all other errors
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     */
    public function testErrorDoesNotRetryRequest()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'Fatal error';
        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'FATAL_ERROR',
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');

            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     *
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::escapeQueryValue()
     */
    public function testCompanyQueryIsEscapedCorrectly()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'company',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Account where Name = \'Some\\\\thing E\\\'lse\' and BillingCountry =  \'Some\\\\Where E\\\'lse\' and BillingCity =  \'Some\\\\Where E\\\'lse\' and BillingState =  \'Some\\\\Where E\\\'lse\'',
                        ]
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getCompany(
            [
                'company' => [
                    'BillingCountry' => 'Some\\Where E\'lse',
                    'BillingCity'    => 'Some\\Where E\'lse',
                    'BillingState'   => 'Some\\Where E\'lse',
                    'Name'           => 'Some\\thing E\'lse',
                ],
            ]
        );
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     *
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::escapeQueryValue()
     */
    public function testContactQueryIsEscapedCorrectly()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Contact',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Contact where email = \'con\\\\tact\\\'email@email.com\'',
                        ]
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getPerson([
            'Contact' => [
                'Email' => 'con\\tact\'email@email.com',
            ],
        ]);
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     *
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::escapeQueryValue()
     */
    public function testLeadQueryIsEscapedCorrectly()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Lead',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Lead where email = \'con\\\\tact\\\'email@email.com\' and ConvertedContactId = NULL',
                        ]
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getPerson([
            'Lead' => [
                'Email' => 'con\\tact\'email@email.com',
            ],
        ]);
    }
}
