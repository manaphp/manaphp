<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use function chr;
use function ord;
use function strlen;

class Str
{
    /** @noinspection SpellCheckingInspection */
    public static function snakelize(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($str)));
    }

    /** @noinspection SpellCheckingInspection */
    public static function pascalize(string $str): string
    {
        if (str_contains($str, '_')) {
            return str_replace('_', '', ucwords($str, '_'));
        } elseif (str_contains($str, '-')) {
            return str_replace('-', '', ucwords($str, '-'));
        } else {
            return ucfirst($str);
        }
    }

    public static function random(int $length, int $base = 62): string
    {
        if ($length < 0) {
            throw new MisuseException('length(%d) is negative number');
        } elseif ($length === 0) {
            return '';
        } elseif ($base === 32) {
            if ($length % 2 === 0) {
                return bin2hex(random_bytes($length / 2));
            } else {
                return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, -1);
            }
        } elseif ($base === 62) {
            $str = base64_encode(random_bytes((int)ceil($length * 0.75)));
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
            throw new NotSupportedException(['base({1}) is not supported', $base]);
        }
    }

    public static function uuid(): string
    {
        $bytes = unpack('N1a/n1b/n1c/n1d/n1e/N1f', random_bytes(16));
        return sprintf(
            '%08x-%04x-%04x-%04x-%04x%08x',
            $bytes['a'], $bytes['b'], ($bytes['c'] & 0x0FFF) | 0x4000, ($bytes['d'] & 0x3FFF) | 0x8000, $bytes['e'],
            $bytes['f']
        );
    }

    public static function camelize(string $str): string
    {
        return lcfirst(self::pascalize($str));
    }

    public static function singular(string $str): string
    {
        if ($str[strlen($str) - 1] === 's') {
            //https://github.com/UlvHare/PHPixie-demo/blob/d000d8f11e6ab7c522feeb4457da5a802ca3e0bc/vendor/phpixie/orm/src/PHPixie/ORM/Configs/Inflector.php
            if (preg_match('#^(.*?us)$|(.*?[sxz])es$|(.*?[^aeioudgkprt]h)es$#', $str, $match)) {
                foreach ($match as $i => $word) {
                    if ($i !== 0 && $word !== '') {
                        return $word;
                    }
                }
                return $str;
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