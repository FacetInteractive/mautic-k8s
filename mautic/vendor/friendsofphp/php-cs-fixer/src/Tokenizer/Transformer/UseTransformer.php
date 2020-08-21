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

namespace PhpCsFixer\Tokenizer\Transformer;

use PhpCsFixer\Tokenizer\AbstractTransformer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Transform T_USE into:
 * - CT::T_USE_TRAIT for imports,
 * - CT::T_USE_LAMBDA for lambda variable uses.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class UseTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return array(CT::T_USE_TRAIT, CT::T_USE_LAMBDA);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId()
    {
        return 50300;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, $index)
    {
        if ($token->isGivenKind(T_USE) && $this->isUseForLambda($tokens, $index)) {
            $tokens[$index] = new Token(array(CT::T_USE_LAMBDA, $token->getContent()));
        }

        if (!$token->isClassy()) {
            return;
        }

        $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
        $prevToken = null === $prevTokenIndex ? null : $tokens[$prevTokenIndex];

        if ($prevToken->isGivenKind(T_DOUBLE_COLON)) {
            return;
        }

        // Skip whole class braces content.
        // That way we can skip whole tokens in class declaration, therefore skip `T_USE` for traits.

        $index = $tokens->getNextTokenOfKind($index, array('{'));
        $innerLimit = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

        while ($index < $innerLimit) {
            $token = $tokens[++$index];

            if (!$token->isGivenKind(T_USE)) {
                continue;
            }

            if ($this->isUseForLambda($tokens, $index)) {
                $tokens[$index] = new Token(array(CT::T_USE_LAMBDA, $token->getContent()));
            } else {
                $tokens[$index] = new Token(array(CT::T_USE_TRAIT, $token->getContent()));
            }
        }
    }

    /**
     * Check if token under given index is `use` statement for lambda function.
     *
     * @param Tokens $tokens
     * @param int    $index
     *
     * @return bool
     */
    private function isUseForLambda(Tokens $tokens, $index)
    {
        $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];

        // test `function () use ($foo) {}` case
        return $nextToken->equals('(');
    }
}
