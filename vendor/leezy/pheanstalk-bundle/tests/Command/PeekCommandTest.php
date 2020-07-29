<?php

namespace Leezy\PheanstalkBundle\Tests\Command;

use Leezy\PheanstalkBundle\Command\PeekCommand;
use Pheanstalk\Job;
use Symfony\Component\Console\Tester\CommandTester;

class PeekCommandTest extends AbstractPheanstalkCommandTest
{
    public function testExecute()
    {
        $args = $this->getCommandArgs();
        $job  = new Job($args['job'], 'data');

        $this->pheanstalk->expects($this->once())->method('peek')->with($job->getId())->will($this->returnValue($job));

        $command = $this->application->find('leezy:pheanstalk:peek');
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);

        $this->assertContains(sprintf('Job id: %d', $job->getId()), $commandTester->getDisplay());
        $this->assertContains(sprintf('Data: %s', $job->getData()), $commandTester->getDisplay());
    }

    /**
     * @inheritdoc
     */
    protected function getCommand()
    {
        return new PeekCommand($this->locator);
    }

    /**
     * @inheritdoc
     */
    protected function getCommandArgs()
    {
        return ['job' => 1234];
    }
}
