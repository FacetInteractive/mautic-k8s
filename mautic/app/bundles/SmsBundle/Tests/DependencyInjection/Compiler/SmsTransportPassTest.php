<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SmsTransportPassTest extends AbstractMauticTestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new SmsTransportPass());
        $container
            ->register('foo')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAliasDefault', 'integrationAlias' => 'fakeIntegrationDefault']);

        $container
            ->register('chocolate')
            ->setPublic(true)
            ->setAbstract(true);

        $container
            ->register('bar')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport');

        $transport = $this->getMockBuilder(TransportChain::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['addTransport'])
                          ->getMock();

        $container
            ->register('mautic.sms.transport_chain')
            ->setClass(get_class($transport))
            ->setArguments(['foo', $this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic')])
            ->setShared(false)
            ->setSynthetic(true)
            ->setAbstract(true);

        $pass = new SmsTransportPass();
        $pass->process($container);

        $this->assertEquals(2, count($container->findTaggedServiceIds('mautic.sms_transport')));

        $methodCalls = $container->getDefinition('mautic.sms.transport_chain')->getMethodCalls();
        $this->assertCount(count($methodCalls), $container->findTaggedServiceIds('mautic.sms_transport'));

        // Translation string
        $this->assertEquals('fakeAliasDefault', $methodCalls[0][1][2]);
        // Integration name/alias
        $this->assertEquals('fakeIntegrationDefault', $methodCalls[0][1][3]);

        // Translation string is set as service ID by default
        $this->assertEquals('bar', $methodCalls[1][1][2]);
        // Integration name/alias is set to service ID by default
        $this->assertEquals('bar', $methodCalls[1][1][3]);
    }
}
