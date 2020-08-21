<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Console\Output;

use PhpCsFixer\Error\Error;
use PhpCsFixer\Linter\LintingException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author SpacePossum
 *
 * @internal
 */
final class ErrorOutput
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $isDecorated;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->isDecorated = $output->isDecorated();
    }

    /**
     * @param string  $process
     * @param Error[] $errors
     */
    public function listErrors($process, array $errors)
    {
        $this->output->writeln(array('', sprintf(
            'Files that were not fixed due to errors reported during %s:',
            $process
        )));

        $showDetails = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;
        $showTrace = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG;
        foreach ($errors as $i => $error) {
            $this->output->writeln(sprintf('%4d) %s', $i + 1, $error->getFilePath()));
            if ($showDetails) {
                $e = $error->getSource();
                if (null !== $e) {
                    $class = sprintf('[%s]', get_class($e));
                    $message = $e->getMessage();
                    $code = $e->getCode();
                    if (0 !== $code) {
                        $message .= " (${code})";
                    }

                    $length = max(strlen($class), strlen($message));
                    $lines = array(
                        '',
                        $class,
                        $message,
                        '',
                    );

                    $this->output->writeln('');

                    foreach ($lines as $line) {
                        if (strlen($line) < $length) {
                            $line .= str_repeat(' ', $length - strlen($line));
                        }

                        $this->output->writeln(sprintf('      <error>  %s  </error>', $this->prepareOutput($line)));
                    }

                    if ($showTrace && !$e instanceof LintingException) { // stack trace of lint exception is of no interest
                        $this->output->writeln('');
                        $stackTrace = $e->getTrace();
                        foreach ($stackTrace as $trace) {
                            if (isset($trace['class'], $trace['function']) && 'Symfony\Component\Console\Command\Command' === $trace['class'] && 'run' === $trace['function']) {
                                $this->output->writeln('      [ ... ]');

                                break;
                            }

                            $this->outputTrace($trace);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $trace
     */
    private function outputTrace(array $trace)
    {
        if (isset($trace['class'], $trace['type'], $trace['function'])) {
            $this->output->writeln(sprintf(
                '      <comment>%s</comment>%s<comment>%s()</comment>',
                $this->prepareOutput($trace['class']),
                $this->prepareOutput($trace['type']),
                $this->prepareOutput($trace['function'])
            ));
        } elseif (isset($trace['function'])) {
            $this->output->writeln(sprintf('      <comment>%s()</comment>', $this->prepareOutput($trace['function'])));
        }

        if (isset($trace['file'])) {
            $this->output->writeln(sprintf('        in <info>%s</info> at line <info>%d</info>', $this->prepareOutput($trace['file']), $trace['line']));
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function prepareOutput($string)
    {
        return $this->isDecorated
            ? OutputFormatter::escape($string)
            : $string
        ;
    }
}
