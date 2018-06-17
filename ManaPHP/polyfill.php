<?php

//(PHP 5 >= 5.5.0)
if (!function_exists('boolval')) {
    function boolval($val)
    {
        return (bool)$val;
    }
}

//(PHP 5 >= 5.5.0)
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No error';
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'State mismatch (invalid or malformed JSON)';
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}

//(PHP 5 >= 5.5.0)
if (!function_exists('hash_pbkdf2')) {
    function hash_pbkdf2($algorithm, $password, $salt, $iterations, $length = 0, $rawOutput = false)
    {
        // Number of blocks needed to create the derived key
        $blocks = ceil($length / strlen(hash($algorithm, null, true)));
        $digest = '';

        for ($i = 1; $i <= $blocks; ++$i) {
            $ib = $block = hash_hmac($algorithm, $salt . pack('N', $i), $password, true);

            // Iterations
            for ($j = 1; $j < $iterations; ++$j) {
                $ib ^= ($block = hash_hmac($algorithm, $block, $password, true));
            }

            $digest .= $ib;
        }

        if (!$rawOutput) {
            $digest = bin2hex($digest);
        }

        return substr($digest, 0, $length);
    }
}

//(PHP 5 >= 5.5.0)
if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $output = array();

        foreach ($input as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($indexKey !== null && array_key_exists($indexKey, $row)) {
                $keySet = true;
                $key = (string)$row[$indexKey];
            }

            if ($columnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($columnKey, $row)) {
                $valueSet = true;
                $value = $row[$columnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }
        }

        return $output;
    }
}

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
        }else{
            throw new \RuntimeException('random_bytes is not be implemented');
        }
    }
}

//(PHP 7)
if (!defined(PHP_INT_MIN)) {
    define(PHP_INT_MIN, ~PHP_INT_MAX);
}

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