<?php
namespace ManaPHP\Utility;

/**
 * Class ManaPHP\Utility\Text
 *
 * @package text
 */
class Text
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
        if (self::contains($str, '_')) {
            $parts = explode('_', $str);
            foreach ($parts as $k => $v) {
                $parts[$k] = ucfirst($v);
            }

            return implode('', $parts);
        } else {
            return ucfirst($str);
        }
    }
}