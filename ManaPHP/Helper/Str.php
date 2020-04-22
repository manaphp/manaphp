<?php

namespace ManaPHP\Helper;

/**
 * Class ManaPHP\Helper\Str
 *
 * @package text
 */
class Str
{
    /**
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     */
    public static function contains($haystack, $needle, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return stripos($haystack, $needle) !== false;
        } else {
            return strpos($haystack, $needle) !== false;
        }

    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return stripos($haystack, $needle) === 0;
        } else {
            return strpos($haystack, $needle) === 0;
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return strripos($haystack, $needle) === strlen($haystack) - strlen($needle);
        } else {
            return substr_compare($haystack, $needle, -strlen($needle)) === 0;
        }

    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function underscore($str)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($str)));
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function camelize($str)
    {
        if (strpos($str, '_') !== false) {
            if (PHP_VERSION_ID >= 50516) {
                //http://php.net/manual/en/function.ucwords.php
                return str_replace('_', '', ucwords($str, '_'));
            } else {
                $parts = explode('_', $str);
                foreach ($parts as $k => $v) {
                    $parts[$k] = ucfirst($v);
                }

                return implode('', $parts);
            }
        } else {
            return ucfirst($str);
        }
    }
}