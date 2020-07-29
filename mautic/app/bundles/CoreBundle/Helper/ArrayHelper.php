<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Helper functions for simpler operations with arrays.
 */
class ArrayHelper
{
    /**
     * If the $key exists in the $origin array then it will return its value.
     *
     * @param mixed $key
     * @param array $origin
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public static function getValue($key, array $origin, $defaultValue = null)
    {
        return array_key_exists($key, $origin) ? $origin[$key] : $defaultValue;
    }

    /**
     * If the $key exists in the $origin array then it will return its value
     * and unsets the $key from the $array.
     *
     * @param mixed $key
     * @param array $origin
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public static function pickValue($key, array &$origin, $defaultValue = null)
    {
        $value = self::getValue($key, $origin, $defaultValue);

        unset($origin[$key]);

        return $value;
    }

    /**
     * Selects keys defined in the $keys array and returns array that contains only those.
     *
     * @param array $keys
     * @param array $origin
     *
     * @return array
     */
    public static function select(array $keys, array $origin)
    {
        return array_filter($origin, function ($value, $key) use ($keys) {
            return in_array($key, $keys, true);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Sum between two array.
     *
     * @param array $a1
     * @param array $b2
     */
    public static function sum(array $a1, array $b2)
    {
        return self::sumOrSub($a1, $b2);
    }

    /**
     * SUBSTRACT between two array.
     *
     * @param array $a1
     * @param array $b2
     *
     * @return array
     */
    public static function sub(array $a1, array $b2)
    {
        return self::sumOrSub($a1, $b2, true);
    }

    /**
     *  SUM/SUBSTRACT between two arrays.
     *
     * @param array $a1
     * @param array $b2
     * @param bool  $subtracted
     *
     * @return array
     */
    private static function sumOrSub(array $a1, array $b2, $subtracted = false)
    {
        return  array_map(function ($x, $y) use ($subtracted) {
            if ($subtracted) {
                return $x - $y;
            } else {
                return $x + $y;
            }
        }, $a1, $b2);
    }
}
