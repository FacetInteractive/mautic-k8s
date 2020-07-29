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

namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class PhpUnitDedicateAssertFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    private static $fixMap = array(
        'array_key_exists' => array('assertArrayNotHasKey', 'assertArrayHasKey'),
        'empty' => array('assertNotEmpty', 'assertEmpty'),
        'file_exists' => array('assertFileNotExists', 'assertFileExists'),
        'is_infinite' => array('assertFinite', 'assertInfinite'),
        'is_nan' => array(false, 'assertNan'),
        'is_null' => array('assertNotNull', 'assertNull'),
        'is_array' => true,
        'is_bool' => true,
        'is_callable' => true,
        'is_double' => true,
        'is_float' => true,
        'is_int' => true,
        'is_integer' => true,
        'is_long' => true,
        'is_numeric' => true,
        'is_object' => true,
        'is_real' => true,
        'is_resource' => true,
        'is_scalar' => true,
        'is_string' => true,
    );

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPUnit assertions like `assertInternalType`, `assertFileExists`, should be used over `assertTrue`.',
            array(
                new CodeSample(
                    '<?php
$this->assertTrue(is_float( $a), "my message");
$this->assertTrue(is_nan($a));
'
                ),
                new CodeSample(
                    '<?php
$this->assertTrue(is_float( $a), "my message");
$this->assertTrue(is_nan($a));
',
                    array('functions' => array('is_nan'))
                ),
            ),
            null,
            'Fixer could be risky if one is overriding PHPUnit\'s native methods.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run after the PhpUnitConstructFixer.
        return -15;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        static $searchSequence = array(
            array(T_VARIABLE, '$this'),
            array(T_OBJECT_OPERATOR, '->'),
            array(T_STRING),
        );

        $index = 1;
        $candidate = $tokens->findSequence($searchSequence, $index);
        while (null !== $candidate) {
            end($candidate);
            $index = $this->getAssertCandidate($tokens, key($candidate));
            if (is_array($index)) {
                $index = $this->fixAssert($tokens, $index);
            }

            ++$index;
            $candidate = $tokens->findSequence($searchSequence, $index);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $values = array(
            'array_key_exists',
            'empty',
            'file_exists',
            'is_infinite',
            'is_nan',
            'is_null',
            'is_array',
            'is_bool',
            'is_callable',
            'is_double',
            'is_float',
            'is_int',
            'is_integer',
            'is_long',
            'is_numeric',
            'is_object',
            'is_real',
            'is_resource',
            'is_scalar',
            'is_string',
        );

        $functions = new FixerOptionBuilder('functions', 'List of assertions to fix.');
        $functions = $functions
            ->setAllowedTypes(array('array'))
            ->setAllowedValues(array(new AllowedValueSubset($values)))
            ->setDefault($values)
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('functions', array($functions));
    }

    /**
     * @param Tokens $tokens
     * @param int    $assertCallIndex Token index of assert method call
     *
     * @return int|int[] indexes of assert call, test call and positive flag, or last index checked
     */
    private function getAssertCandidate(Tokens $tokens, $assertCallIndex)
    {
        $content = strtolower($tokens[$assertCallIndex]->getContent());
        if ('asserttrue' === $content) {
            $isPositive = 1;
        } elseif ('assertfalse' === $content) {
            $isPositive = 0;
        } else {
            return $assertCallIndex;
        }

        // test candidate for simple calls like: ([\]+'some fixable call'(...))
        $assertCallOpenIndex = $tokens->getNextMeaningfulToken($assertCallIndex);
        if (!$tokens[$assertCallOpenIndex]->equals('(')) {
            return $assertCallIndex;
        }

        $testDefaultNamespaceTokenIndex = false;
        $testIndex = $tokens->getNextMeaningfulToken($assertCallOpenIndex);

        if (!$tokens[$testIndex]->isGivenKind(array(T_EMPTY, T_STRING))) {
            if (!$tokens[$testIndex]->isGivenKind(T_NS_SEPARATOR)) {
                return $testIndex;
            }

            $testDefaultNamespaceTokenIndex = $testIndex;
            $testIndex = $tokens->getNextMeaningfulToken($testIndex);
        }

        $testOpenIndex = $tokens->getNextMeaningfulToken($testIndex);
        if (!$tokens[$testOpenIndex]->equals('(')) {
            return $testOpenIndex;
        }

        $testCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $testOpenIndex);

        $assertCallCloseIndex = $tokens->getNextMeaningfulToken($testCloseIndex);
        if (!$tokens[$assertCallCloseIndex]->equalsAny(array(')', ','))) {
            return $assertCallCloseIndex;
        }

        return array(
            $isPositive,
            $assertCallIndex,
            $assertCallOpenIndex,
            $testDefaultNamespaceTokenIndex,
            $testIndex,
            $testOpenIndex,
            $testCloseIndex,
            $assertCallCloseIndex,
        );
    }

    /**
     * @param Tokens $tokens
     * @param array  $assertIndexes
     *
     * @return int index up till processed, number of tokens added
     */
    private function fixAssert(Tokens $tokens, array $assertIndexes)
    {
        list(
            $isPositive,
            $assertCallIndex,
            ,
            $testDefaultNamespaceTokenIndex,
            $testIndex,
            $testOpenIndex,
            $testCloseIndex,
            $assertCallCloseIndex
        ) = $assertIndexes;

        $content = strtolower($tokens[$testIndex]->getContent());
        if (!in_array($content, $this->configuration['functions'], true)) {
            return $assertCallCloseIndex;
        }

        if (is_array(self::$fixMap[$content])) {
            if (false !== self::$fixMap[$content][$isPositive]) {
                $tokens[$assertCallIndex] = new Token(array(T_STRING, self::$fixMap[$content][$isPositive]));
                $this->removeFunctionCall($tokens, $testDefaultNamespaceTokenIndex, $testIndex, $testOpenIndex, $testCloseIndex);
            }

            return $assertCallCloseIndex;
        }

        $type = substr($content, 3);
        $tokens[$assertCallIndex] = new Token(array(T_STRING, $isPositive ? 'assertInternalType' : 'assertNotInternalType'));
        $tokens[$testIndex] = new Token(array(T_CONSTANT_ENCAPSED_STRING, "'".$type."'"));
        $tokens[$testOpenIndex] = new Token(',');
        $tokens->clearTokenAndMergeSurroundingWhitespace($testCloseIndex);

        if (!$tokens[$testOpenIndex + 1]->isWhitespace()) {
            $tokens->insertAt($testOpenIndex + 1, new Token(array(T_WHITESPACE, ' ')));
        }

        if (false !== $testDefaultNamespaceTokenIndex) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($testDefaultNamespaceTokenIndex);
        }

        return $assertCallCloseIndex;
    }

    /**
     * @param Tokens    $tokens
     * @param false|int $callNSIndex
     * @param int       $callIndex
     * @param int       $openIndex
     * @param int       $closeIndex
     */
    private function removeFunctionCall(Tokens $tokens, $callNSIndex, $callIndex, $openIndex, $closeIndex)
    {
        $tokens->clearTokenAndMergeSurroundingWhitespace($callIndex);
        if (false !== $callNSIndex) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($callNSIndex);
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($openIndex);
        $tokens->clearTokenAndMergeSurroundingWhitespace($closeIndex);
    }
}
