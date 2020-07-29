<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Twilio\Exceptions\ConfigurationException;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationHelper;

    /**
     * @var AbstractIntegration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationObject;

    protected function setUp()
    {
        $this->integrationHelper = $this->createMock(IntegrationHelper::class);

        $integrationSettings = new Integration();
        $integrationSettings->setIsPublished(true);
        $integrationSettings->setFeatureSettings(['sending_phone_number' => '123']);
        $this->integrationObject = $this->createMock(AbstractIntegration::class);
        $this->integrationObject->method('getIntegrationSettings')
            ->willReturn($integrationSettings);

        $this->integrationHelper->method('getIntegrationObject')
            ->with('Twilio')
            ->willReturn($this->integrationObject);
    }

    public function testGetSendingNumber()
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('123', $this->getConfiguration()->getSendingNumber());
    }

    public function testGetAccountSid()
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('username', $this->getConfiguration()->getAccountSid());
    }

    public function testGetAuthToken()
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('password', $this->getConfiguration()->getAuthToken());
    }

    public function testConfigurationExceptionThrownIfNotPublished()
    {
    }

    public function testConfigurationExceptionThrownWithoutSendingNumber()
    {
        $this->expectException(ConfigurationException::class);

        $this->integrationObject->getIntegrationSettings()->setFeatureSettings(['sending_phone_number' => '']);

        $this->getConfiguration()->getSendingNumber();
    }

    public function testConfigurationExceptionThrownWithoutUsername()
    {
        $this->expectException(ConfigurationException::class);
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => '',
                    'password' => 'password',
                ]
            );
        $this->getConfiguration()->getSendingNumber();
    }

    public function testConfigurationExceptionThrownWithoutPassword()
    {
        $this->expectException(ConfigurationException::class);
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => '',
                ]
            );
        $this->getConfiguration()->getSendingNumber();
    }

    /**
     * @return Configuration
     */
    private function getConfiguration()
    {
        return new Configuration($this->integrationHelper);
    }
}
