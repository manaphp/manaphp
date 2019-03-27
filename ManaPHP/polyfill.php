<?php

if (!function_exists('random_bytes')) {
    function random_bytes($length)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            return openssl_random_pseudo_bytes($length);
        } elseif (function_exists('mcrypt_create_iv')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            /** @noinspection PhpDeprecationInspection */
            return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        } else {
            throw new \RuntimeException('random_bytes is not be implemented');
        }
    }
}

//(PHP 7)
defined('PHP_INT_MIN') or define('PHP_INT_MIN', ~PHP_INT_MAX);

//(PHP 7)
if (!function_exists('random_int')) {
    //https://github.com/paragonie/random_compat/blob/master/lib/random_int.php
    function random_int($min, $max)
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum value must be less than or equal to the maximum value');
        }

        $min = (int)$min;
        $max = (int)$max;

        if ($max === $min) {
            return $min;
        }

        $attempts = $bits = $bytes = $mask = $valueShift = 0;

        $range = $max - $min;
        if (!is_int($range)) {
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            while ($range > 0) {
                if ($bits % 8 === 0) {
                    ++$bytes;
                }
                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }
            $valueShift = $min;
        }

        $val = 0;
        do {
            if ($attempts > 128) {
                throw new \RuntimeException('random_int: RNG is broken - too many rejections');
            }

            $randomByteString = random_bytes($bytes);
            $val &= 0;
            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($randomByteString[$i]) << ($i * 8);
            }

            $val &= $mask;
            $val += $valueShift;

            ++$attempts;
        } while (!is_int($val) || $val > $max || $val < $min);

        /** @noinspection UnnecessaryCastingInspection */
        return (int)$val;
    }
}

if (!function_exists('intdiv')) {
    function intdiv($dividend, $divisor)
    {
        return ($dividend - ($dividend % $divisor) / $divisor);
    }
}