<?php

namespace ManaPHP\Helper;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;

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
            return str_contains($haystack, $needle);
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
            return str_starts_with($haystack, $needle);
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
            return str_ends_with($haystack, $needle);
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
        if (str_contains($str, '_')) {
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

    /**
     * @param string $length
     * @param int    $base
     *
     * @return string
     */
    public static function random($length, $base = 62)
    {
        if ($length < 0) {
            throw new MisuseException('length(%d) is negative number');
        } elseif ($length === 0) {
            return '';
        } elseif ($base === 32) {
            if ($length % 2 === 0) {
                return bin2hex(random_bytes($length / 2));
            } else {
                return substr(bin2hex(random_bytes(ceil($length / 2))), 0, -1);
            }
        } elseif ($base === 62) {
            $str = base64_encode(random_bytes(ceil($length * 0.75)));
            $str = strtr($str, ['+' => '0', '/' => '5', '=' => '9']);
            return substr($str, 0, $length);
        } elseif ($base < 62) {
            $str = '';

            $bytes = random_bytes($length);
            for ($i = 0; $i < $length; $i++) {
                $r = ord($bytes[$i]) % $base;

                if ($r < 10) {
                    $str .= chr(ord('0') + $r);
                } elseif ($r < 36) {
                    $str .= chr(ord('a') + $r - 10);
                } else {
                    $str .= chr(ord('A') + $r - 36);
                }
            }
            return $str;
        } else {
            throw new NotSupportedException(['base(%d) is not supported', $base]);
        }
    }

    /**
     * @return string
     */
    public static function uuid()
    {
        $bytes = unpack('N1a/n1b/n1c/n1d/n1e/N1f', random_bytes(16));
        return sprintf(
            '%08x-%04x-%04x-%04x-%04x%08x',
            $bytes['a'], $bytes['b'], ($bytes['c'] & 0x0FFF) | 0x4000, ($bytes['d'] & 0x3FFF) | 0x8000, $bytes['e'],
            $bytes['f']
        );
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function variablize($str)
    {
        return lcfirst(self::camelize($str));
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function singular($str)
    {
        if ($str[strlen($str) - 1] === 's') {
            //https://github.com/UlvHare/PHPixie-demo/blob/d000d8f11e6ab7c522feeb4457da5a802ca3e0bc/vendor/phpixie/orm/src/PHPixie/ORM/Configs/Inflector.php
            if (preg_match('#^(.*?us)$|(.*?[sxz])es$|(.*?[^aeioudgkprt]h)es$#', $str, $match)) {
                foreach ($match as $i => $word) {
                    if ($i !== 0 && $word !== '') {
                        return $word;
                    }
                }
            } elseif (preg_match('#^(.*?[^aeiou])ies$#', $str, $match)) {
                return $match[1] . 'y';
            } else {
                return substr($str, 0, -1);
            }
        } else {
            return $str;
        }
    }
}