<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Command\ProcessEmailQueueCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessEmailQueueCommandTest extends \PHPUnit_Framework_TestCase
{
    private $coreParametersHelper;
    private $dispatcher;
    private $container;
    private $transport;
    private $application;

    /**
     * @var ProcessEmailQueueCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->container            = $this->createMock(Container::class);
        $this->transport            = $this->createMock(\Swift_Transport::class);
        $this->application          = $this->createMock(Application::class);

        $this->application->method('getHelperSet')
            ->willReturn($this->createMock(HelperSet::class));

        $inputDefinition = $this->createMock(InputDefinition::class);

        $this->application->method('getDefinition')
            ->willReturn($inputDefinition);

        $inputDefinition->method('getOptions')
            ->willReturn([]);

        $this->command = new ProcessEmailQueueCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication($this->application);

        $this->container->method('get')
            ->withConsecutive(
                ['event_dispatcher'],
                ['mautic.helper.core_parameters'],
                ['swiftmailer.transport.real']
            )->willReturnOnConsecutiveCalls(
                $this->dispatcher,
                $this->coreParametersHelper,
                $this->transport
            );
    }

    public function testCommandWhenQueueIsDisabled()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertSame("Mautic is not set to queue email.\n", $output->fetch());
    }

    /**
     * Ensure this error won't happen:.
     *
     * Error: Swift_Mime_SimpleMimeEntity::_getHeaderFieldModel(): The script tried to
     * execute a method or access a property of an incomplete ob  ject. Please ensure
     * that the class definition "Swift_Mime_SimpleHeaderSet" of the object you are
     * trying to operate on was loaded _before_ unserialize() gets called or provide
     * an autoloader to load the class definition
     */
    public function testCommandWhenQueueIsEnabled()
    {
        $tryAgainMessageFile    = '0HZYoueQaC.tryagain';
        $tmpSpoolDir            = sys_get_temp_dir().'/mauticSpoolTestDir';
        $tryAgainMessage        = __DIR__.'/../Data/SpoolSample/'.$tryAgainMessageFile;
        $tmpTryAgainMessageFile = $tmpSpoolDir.'/'.$tryAgainMessageFile;
        if (!file_exists($tmpSpoolDir)) {
            mkdir($tmpSpoolDir, 0777, true);
        }
        copy($tryAgainMessage, $tmpTryAgainMessageFile);

        $this->coreParametersHelper->method('getParameter')
            ->withConsecutive(['mailer_spool_type'])
            ->willReturnOnConsecutiveCalls(true);

        $this->container->method('getParameter')
            ->withConsecutive(
                ['mautic.mailer_spool_path'],
                ['mautic.mailer_spool_msg_limit']
            )
            ->will($this->onConsecutiveCalls(
                $tmpSpoolDir,
                10
            ));

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (\Swift_Message $message) {
                // This triggers the error this test was created for.
                $message->getReturnPath();

                return true;
            }));

        $this->application->expects($this->once())
            ->method('find')
            ->with('swiftmailer:spool:send')
            ->willReturn($this->createMock(Command::class));

        $input  = new ArrayInput(['--bypass-locking' => true, '--clear-timeout' => 10]);
        $output = new BufferedOutput();
        $this->assertSame(0, $this->command->run($input, $output));

        // The file is deleted after successful send.
        $this->assertFalse(file_exists($tmpTryAgainMessageFile));

        // Cleanup.
        unset($tmpSpoolDir);
    }
}
