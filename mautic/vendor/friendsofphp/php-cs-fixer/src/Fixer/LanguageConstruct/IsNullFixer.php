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

namespace PhpCsFixer\Fixer\LanguageConstruct;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 */
final class IsNullFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replaces `is_null($var)` expression with `null === $var`.',
            array(
                new CodeSample("<?php\n\$a = is_null(\$b);"),
                new CodeSample("<?php\n\$a = is_null(\$b);", array('use_yoda_style' => false)),
            ),
            null,
            'Risky when the function `is_null` is overridden.'
        );
    }

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
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        static $sequenceNeeded = array(array(T_STRING, 'is_null'), '(');

        $currIndex = 0;
        while (null !== $currIndex) {
            $matches = $tokens->findSequence($sequenceNeeded, $currIndex, $tokens->count() - 1, false);

            // stop looping if didn't find any new matches
            if (null === $matches) {
                break;
            }

            // 0 and 1 accordingly are "is_null", "(" tokens
            $matches = array_keys($matches);

            // move the cursor just after the sequence
            list($isNullIndex, $currIndex) = $matches;

            $next = $tokens->getNextMeaningfulToken($currIndex);
            if ($tokens[$next]->equals(')')) {
                continue;
            }

            // skip all expressions which are not a function reference
            $inversionCandidateIndex = $prevTokenIndex = $tokens->getPrevMeaningfulToken($matches[0]);
            $prevToken = $tokens[$prevTokenIndex];
            if ($prevToken->isGivenKind(array(T_DOUBLE_COLON, T_NEW, T_OBJECT_OPERATOR, T_FUNCTION))) {
                continue;
            }

            // handle function references with namespaces
            if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
                $inversionCandidateIndex = $twicePrevTokenIndex = $tokens->getPrevMeaningfulToken($prevTokenIndex);
                /** @var Token $twicePrevToken */
                $twicePrevToken = $tokens[$twicePrevTokenIndex];
                if ($twicePrevToken->isGivenKind(array(T_DOUBLE_COLON, T_NEW, T_OBJECT_OPERATOR, T_FUNCTION, T_STRING, CT::T_NAMESPACE_OPERATOR))) {
                    continue;
                }

                // get rid of the root namespace when it used and check if the inversion operator provided
                $tokens->removeTrailingWhitespace($prevTokenIndex);
                $tokens->clearAt($prevTokenIndex);
            }

            // check if inversion being used, text comparison is due to not existing constant
            $isInvertedNullCheck = false;
            if ($tokens[$inversionCandidateIndex]->equals('!')) {
                $isInvertedNullCheck = true;

                // get rid of inverting for proper transformations
                $tokens->removeTrailingWhitespace($inversionCandidateIndex);
                $tokens->clearAt($inversionCandidateIndex);
            }

            // before getting rind of `()` around a parameter, ensure it's not assignment/ternary invariant
            $referenceEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $matches[1]);
            $isContainingDangerousConstructs = false;
            for ($paramTokenIndex = $matches[1]; $paramTokenIndex <= $referenceEnd; ++$paramTokenIndex) {
                if (in_array($tokens[$paramTokenIndex]->getContent(), array('?', '?:', '='), true)) {
                    $isContainingDangerousConstructs = true;

                    break;
                }
            }

            // edge cases: is_null() followed/preceded by ==, ===, !=, !==, <>
            $parentLeftToken = $tokens[$tokens->getPrevMeaningfulToken($isNullIndex)];
            $parentRightToken = $tokens[$tokens->getNextMeaningfulToken($referenceEnd)];
            $parentOperations = array(T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL);
            $wrapIntoParentheses = $parentLeftToken->isGivenKind($parentOperations) || $parentRightToken->isGivenKind($parentOperations);

            if (!$isContainingDangerousConstructs) {
                if (!$wrapIntoParentheses) {
                    // closing parenthesis removed with leading spaces
                    $tokens->removeLeadingWhitespace($referenceEnd);
                    $tokens->clearAt($referenceEnd);
                }

                // opening parenthesis removed with trailing spaces
                $tokens->removeLeadingWhitespace($matches[1]);
                $tokens->removeTrailingWhitespace($matches[1]);
                $tokens->clearAt($matches[1]);
            }

            // sequence which we'll use as a replacement
            $replacement = array(
                new Token(array(T_STRING, 'null')),
                new Token(array(T_WHITESPACE, ' ')),
                new Token($isInvertedNullCheck ? array(T_IS_NOT_IDENTICAL, '!==') : array(T_IS_IDENTICAL, '===')),
                new Token(array(T_WHITESPACE, ' ')),
            );

            if (true === $this->configuration['use_yoda_style']) {
                if ($wrapIntoParentheses) {
                    array_unshift($replacement, new Token('('));
                }

                $tokens->overrideRange($isNullIndex, $isNullIndex, $replacement);
            } else {
                $replacement = array_reverse($replacement);
                if ($isContainingDangerousConstructs) {
                    array_unshift($replacement, new Token(')'));
                }

                if ($wrapIntoParentheses) {
                    $replacement[] = new Token(')');
                    $tokens[$isNullIndex] = new Token('(');
                } else {
                    $tokens->clearAt($isNullIndex);
                }

                $tokens->overrideRange($referenceEnd, $referenceEnd, $replacement);
            }

            // nested is_null calls support
            $currIndex = $isNullIndex;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $yoda = new FixerOptionBuilder('use_yoda_style', 'Whether Yoda style conditions should be used.');
        $yoda = $yoda
            ->setAllowedTypes(array('bool'))
            ->setDefault(true)
            ->getOption()
        ;

        return new FixerConfigurationResolver(array($yoda));
    }
}
