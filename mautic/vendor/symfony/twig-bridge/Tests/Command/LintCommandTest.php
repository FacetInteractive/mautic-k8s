<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Tests\Command;

use Symfony\Bridge\Twig\Command\LintCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class LintCommandTest extends \PHPUnit_Framework_TestCase
{
    private $files;

    public function testLintCorrectFile()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile('{{ foo }}');

        $ret = $tester->execute(array('filename' => array($filename)), array('verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false));

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertRegExp('/^\/\/ OK in /', trim($tester->getDisplay()));
    }

    public function testLintIncorrectFile()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile('{{ foo');

        $ret = $tester->execute(array('filename' => array($filename)), array('decorated' => false));

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertRegExp('/ERROR  in \S+ \(line /', trim($tester->getDisplay()));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLintFileNotReadable()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile('');
        unlink($filename);

        $ret = $tester->execute(array('filename' => array($filename)), array('decorated' => false));
    }

    public function testLintFileCompileTimeException()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile("{{ 2|number_format(2, decimal_point='.', ',') }}");

        $ret = $tester->execute(array('filename' => array($filename)), array('decorated' => false));

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertRegExp('/ERROR  in \S+ \(line /', trim($tester->getDisplay()));
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester()
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem());

        $command = new LintCommand();
        $command->setTwigEnvironment($twig);

        $application = new Application();
        $application->add($command);
        $command = $application->find('lint:twig');

        return new CommandTester($command);
    }

    /**
     * @return string Path to the new file
     */
    private function createFile($content)
    {
        $filename = tempnam(sys_get_temp_dir(), 'sf-');
        file_put_contents($filename, $content);

        $this->files[] = $filename;

        return $filename;
    }

    protected function setUp()
    {
        $this->files = array();
    }

    protected function tearDown()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
