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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFunctionReferenceFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocAddMissingParamAnnotationFixer extends AbstractFunctionReferenceFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPDoc should contain `@param` for all params.',
            array(
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}'
                ),
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}',
                    array('only_untyped' => true)
                ),
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}',
                    array('only_untyped' => false)
                ),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // must be run after PhpdocNoAliasTagFixer
        // must be run before PhpdocAlignFixer and PhpdocNoEmptyReturnFixer
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            $mainIndex = $index;
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $tokenContent = $token->getContent();

            if (false !== stripos($tokenContent, 'inheritdoc')) {
                continue;
            }

            // ignore one-line PHPDocs like `/** foo */`, as there is no place to put new annotations
            if (false === strpos($tokenContent, "\n")) {
                continue;
            }

            $index = $tokens->getNextMeaningfulToken($index);

            if (null === $index) {
                return;
            }

            while ($tokens[$index]->isGivenKind(array(
                T_ABSTRACT,
                T_FINAL,
                T_PRIVATE,
                T_PROTECTED,
                T_PUBLIC,
                T_STATIC,
                T_VAR,
            ))) {
                $index = $tokens->getNextMeaningfulToken($index);
            }

            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $openIndex = $tokens->getNextTokenOfKind($index, array('('));
            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);

            $arguments = array();

            foreach ($argumentsAnalyzer->getArguments($tokens, $openIndex, $index) as $start => $end) {
                $argumentInfo = $this->prepareArgumentInformation($tokens, $start, $end);

                if (!$this->configuration['only_untyped'] || '' === $argumentInfo['type']) {
                    $arguments[$argumentInfo['name']] = $argumentInfo;
                }
            }

            if (!count($arguments)) {
                continue;
            }

            $doc = new DocBlock($tokenContent);
            $lastParamLine = null;

            foreach ($doc->getAnnotationsOfType('param') as $annotation) {
                $pregMatched = Preg::match('/^[^$]+(\$\w+).*$/s', $annotation->getContent(), $matches);

                if (1 === $pregMatched) {
                    unset($arguments[$matches[1]]);
                }

                $lastParamLine = max($lastParamLine, $annotation->getEnd());
            }

            if (!count($arguments)) {
                continue;
            }

            $lines = $doc->getLines();
            $linesCount = count($lines);

            Preg::match('/^(\s*).*$/', $lines[$linesCount - 1]->getContent(), $matches);
            $indent = $matches[1];

            $newLines = array();

            foreach ($arguments as $argument) {
                $type = $argument['type'] ?: 'mixed';

                if ('?' !== $type[0] && 'null' === strtolower($argument['default'])) {
                    $type = 'null|'.$type;
                }

                $newLines[] = new Line(sprintf(
                    '%s* @param %s %s%s',
                    $indent,
                    $type,
                    $argument['name'],
                    $this->whitespacesConfig->getLineEnding()
                ));
            }

            array_splice(
                $lines,
                $lastParamLine ? $lastParamLine + 1 : $linesCount - 1,
                0,
                $newLines
            );

            $tokens[$mainIndex] = new Token(array(T_DOC_COMMENT, implode('', $lines)));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $onlyUntyped = new FixerOptionBuilder('only_untyped', 'Whether to add missing `@param` annotations for untyped parameters only.');
        $onlyUntyped = $onlyUntyped
            ->setDefault(true)
            ->setAllowedTypes(array('bool'))
            ->getOption()
        ;

        return new FixerConfigurationResolver(array($onlyUntyped));
    }

    /**
     * @param Tokens $tokens
     * @param int    $start
     * @param int    $end
     *
     * @return array
     */
    private function prepareArgumentInformation(Tokens $tokens, $start, $end)
    {
        $info = array(
            'default' => '',
            'name' => '',
            'type' => '',
        );

        $sawName = false;

        for ($index = $start; $index <= $end; ++$index) {
            $token = $tokens[$index];

            if ($token->isComment() || $token->isWhitespace()) {
                continue;
            }

            if ($token->isGivenKind(T_VARIABLE)) {
                $sawName = true;
                $info['name'] = $token->getContent();

                continue;
            }

            if ($token->equals('=')) {
                continue;
            }

            if ($sawName) {
                $info['default'] .= $token->getContent();
            } else {
                $info['type'] .= $token->getContent();
            }
        }

        return $info;
    }
}
