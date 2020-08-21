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

namespace PhpCsFixer\Tests\Test;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Linter\CachingLinter;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Prophecy\Argument;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    /**
     * @var LinterInterface
     */
    protected $linter;

    /**
     * @var null|ConfigurableFixerInterface|FixerInterface
     */
    protected $fixer;

    protected function setUp()
    {
        parent::setUp();

        $this->linter = $this->getLinter();
        $this->fixer = $this->createFixer();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->linter = null;
        $this->fixer = null;
    }

    /**
     * @return FixerInterface
     */
    protected function createFixer()
    {
        $fixerClassName = preg_replace('/^(PhpCsFixer)\\\\Tests(\\\\.+)Test$/', '$1$2', get_called_class());

        return new $fixerClassName();
    }

    /**
     * @param string $filename
     *
     * @return \SplFileInfo
     */
    protected function getTestFile($filename = __FILE__)
    {
        static $files = array();

        if (!isset($files[$filename])) {
            $files[$filename] = new \SplFileInfo($filename);
        }

        return $files[$filename];
    }

    /**
     * Tests if a fixer fixes a given string to match the expected result.
     *
     * It is used both if you want to test if something is fixed or if it is not touched by the fixer.
     * It also makes sure that the expected output does not change when run through the fixer. That means that you
     * do not need two test cases like [$expected] and [$expected, $input] (where $expected is the same in both cases)
     * as the latter covers both of them.
     * This method throws an exception if $expected and $input are equal to prevent test cases that accidentally do
     * not test anything.
     *
     * @param string            $expected The expected fixer output
     * @param null|string       $input    The fixer input, or null if it should intentionally be equal to the output
     * @param null|\SplFileInfo $file     The file to fix, or null if unneeded
     */
    protected function doTest($expected, $input = null, \SplFileInfo $file = null)
    {
        if ($expected === $input) {
            throw new \InvalidArgumentException('Input parameter must not be equal to expected parameter.');
        }

        $file = $file ?: $this->getTestFile();
        $fileIsSupported = $this->fixer->supports($file);

        if (null !== $input) {
            $this->assertNull($this->lintSource($input));

            Tokens::clearCache();
            $tokens = Tokens::fromCode($input);

            if ($fileIsSupported) {
                $this->assertTrue($this->fixer->isCandidate($tokens), 'Fixer must be a candidate for input code.');
                $this->assertFalse($tokens->isChanged(), 'Fixer must not touch Tokens on candidate check.');
                $fixResult = $this->fixer->fix($file, $tokens);
                $this->assertNull($fixResult, '->fix method must return null.');
            }

            $this->assertThat(
                $tokens->generateCode(),
                self::createIsIdenticalStringConstraint($expected),
                'Code build on input code must match expected code.'
            );
            $this->assertTrue($tokens->isChanged(), 'Tokens collection built on input code must be marked as changed after fixing.');

            $tokens->clearEmptyTokens();

            $this->assertSame(
                count($tokens),
                count(array_unique(array_map(function (Token $token) {
                    return spl_object_hash($token);
                }, $tokens->toArray()))),
                'Token items inside Tokens collection must be unique.'
            );

            Tokens::clearCache();
            $expectedTokens = Tokens::fromCode($expected);
            $this->assertTokens($expectedTokens, $tokens);
        }

        $this->assertNull($this->lintSource($expected));

        Tokens::clearCache();
        $tokens = Tokens::fromCode($expected);

        if ($fileIsSupported) {
            $fixResult = $this->fixer->fix($file, $tokens);
            $this->assertNull($fixResult, '->fix method must return null.');
        }

        $this->assertThat(
            $tokens->generateCode(),
            self::createIsIdenticalStringConstraint($expected),
            'Code build on expected code must not change.'
        );
        $this->assertFalse($tokens->isChanged(), 'Tokens collection built on expected code must not be marked as changed after fixing.');
    }

    /**
     * @param string $source
     *
     * @return null|string
     */
    protected function lintSource($source)
    {
        try {
            $this->linter->lintSource($source)->check();
        } catch (\Exception $e) {
            return $e->getMessage()."\n\nSource:\n${source}";
        }
    }

    private function assertTokens(Tokens $expectedTokens, Tokens $inputTokens)
    {
        foreach ($expectedTokens as $index => $expectedToken) {
            $option = array('JSON_PRETTY_PRINT');
            $inputToken = $inputTokens[$index];

            $this->assertTrue(
                $expectedToken->equals($inputToken),
                sprintf("The token at index %d must be:\n%s,\ngot:\n%s.", $index, $expectedToken->toJson($option), $inputToken->toJson($option))
            );

            $expectedTokenKind = $expectedToken->isArray() ? $expectedToken->getId() : $expectedToken->getContent();
            $this->assertTrue(
                $inputTokens->isTokenKindFound($expectedTokenKind),
                sprintf('The token kind %s must be found in fixed tokens collection.', $expectedTokenKind)
            );
        }

        $this->assertSame($expectedTokens->count(), $inputTokens->count(), 'Both collections must have the same length.');
    }

    /**
     * @return LinterInterface
     */
    private function getLinter()
    {
        static $linter = null;

        if (null === $linter) {
            if (getenv('SKIP_LINT_TEST_CASES')) {
                $linterProphecy = $this->prophesize('PhpCsFixer\Linter\LinterInterface');
                $linterProphecy
                    ->lintSource(Argument::type('string'))
                    ->willReturn($this->prophesize('PhpCsFixer\Linter\LintingResultInterface')->reveal());

                $linter = $linterProphecy->reveal();
            } else {
                $linter = new CachingLinter(new Linter());
            }
        }

        return $linter;
    }

    /**
     * @todo Remove me when this class will end up in dedicated package.
     *
     * @param string $expected
     */
    private static function createIsIdenticalStringConstraint($expected)
    {
        $candidates = array_filter(array(
            'PhpCsFixer\PhpunitConstraintIsIdenticalString\Constraint\IsIdenticalString',
            'PHPUnit\Framework\Constraint\IsIdentical',
            'PHPUnit_Framework_Constraint_IsIdentical',
        ), function ($className) { return class_exists($className); });

        if (empty($candidates)) {
            throw new \RuntimeException('PHPUnit not installed?!');
        }

        $candidate = array_shift($candidates);

        return new $candidate($expected);
    }
}
