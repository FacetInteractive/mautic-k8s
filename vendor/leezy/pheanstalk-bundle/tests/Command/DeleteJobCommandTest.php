<?php

namespace Leezy\PheanstalkBundle\Tests\Command;

use Leezy\PheanstalkBundle\Command\DeleteJobCommand;
use Pheanstalk\Job;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteJobCommandTest extends AbstractPheanstalkCommandTest
{
    public function testExecute()
    {
        $args = $this->getCommandArgs();
        $job  = new Job($args['job'], 'test');

        $this->pheanstalk->expects($this->once())->method('peek')->will($this->returnValue($job));
        $this->pheanstalk->expects($this->once())->method('delete')->with($job);

        $command = $this->application->find('leezy:pheanstalk:delete-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);

        $this->assertContains(sprintf('Job %d deleted', $job->getId()), $commandTester->getDisplay());
    }

    /**
     * @inheritdoc
     */
    protected function getCommand()
    {
        return new DeleteJobCommand($this->locator);
    }

    /**
     * @inheritdoc
     */
    protected function getCommandArgs()
    {
        return ['job' => 1234];
    }
}
