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

namespace PhpCsFixer;

use PhpCsFixer\Tokenizer\Token;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Graham Campbell <graham@alt-three.com>
 * @author Odín del Río <odin.drp@gmail.com>
 *
 * @internal
 */
final class Utils
{
    /**
     * Calculate a bitmask for given constant names.
     *
     * @param string[] $options constant names
     *
     * @return int
     */
    public static function calculateBitmask(array $options)
    {
        $bitmask = 0;

        foreach ($options as $optionName) {
            if (defined($optionName)) {
                $bitmask |= constant($optionName);
            }
        }

        return $bitmask;
    }

    /**
     * Converts a camel cased string to an snake cased string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function camelCaseToUnderscore($string)
    {
        return Preg::replaceCallback(
            '/(^|[a-z0-9])([A-Z])/',
            function (array $matches) {
                return strtolower('' !== $matches[1] ? $matches[1].'_'.$matches[2] : $matches[2]);
            },
            $string
        );
    }

    /**
     * Compare two integers for equality.
     *
     * We'll return 0 if they're equal, 1 if the first is bigger than the
     * second, and -1 if the second is bigger than the first.
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    public static function cmpInt($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return $a < $b ? -1 : 1;
    }

    /**
     * Split a multi-line string up into an array of strings.
     *
     * We're retaining a newline character at the end of non-blank lines, and
     * discarding other lines, so this function is unsuitable for anyone for
     * wishing to retain the exact number of line endings. If a single-line
     * string is passed, we'll just return an array with a element.
     *
     * @param string $content
     *
     * @return string[]
     */
    public static function splitLines($content)
    {
        Preg::matchAll("/[^\n\r]+[\r\n]*/", $content, $matches);

        return $matches[0];
    }

    /**
     * Calculate the trailing whitespace.
     *
     * What we're doing here is grabbing everything after the final newline.
     *
     * @param Token $token
     *
     * @return string
     */
    public static function calculateTrailingWhitespaceIndent(Token $token)
    {
        if (!$token->isWhitespace()) {
            throw new \InvalidArgumentException(sprintf('The given token must be whitespace, got "%s".', $token->getName()));
        }

        $str = strrchr(
            str_replace(array("\r\n", "\r"), "\n", $token->getContent()),
            "\n"
        );

        if (false === $str) {
            return '';
        }

        return ltrim($str, "\n");
    }
}
