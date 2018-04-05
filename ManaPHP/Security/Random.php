<?php
namespace ManaPHP\Security;

use ManaPHP\Component;

/**
 * Class ManaPHP\Security\Random
 *
 * @package random
 */
class Random extends Component implements RandomInterface
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function getByte($length)
    {
        if ($length === 0) {
            return '';
        }

        if (function_exists('random_bytes')) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            return openssl_random_pseudo_bytes($length);
        } elseif (file_exists('/dev/urandom')) {
            $handle = fopen('/dev/urandom', 'rb');

            stream_set_read_buffer($handle, 0);
            $r = fread($handle, $length);
            fclose($handle);

            return $r;
        } else {
            $r = '';

            do {
                $r .= pack('L', mt_rand());
            } while (strlen($r) < $length);

            return substr($r, 0, $length);
        }
    }

    /**
     * @param int $length
     * @param int $base
     *
     * @return string
     */
    public function getBase($length, $base = 62)
    {
        $str = '';

        $bytes = $this->getByte($length);
        /** @noinspection ForeachInvariantsInspection */
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
    }

    /**
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public function getInt($min = 0, $max = 2147483647)
    {
        /** @noinspection TypeUnsafeComparisonInspection */
        if ($min == $max) {
            return $min;
        } else {
            $ar = unpack('l', $this->getByte(4));
            return $min + abs($ar[1]) % ($max - $min + 1);
        }
    }

    /**
     * @param float $min
     * @param float $max
     *
     * @return float
     */
    public function getFloat($min = 0.0, $max = 1.0)
    {
        return $min + $this->getInt() / 2147483647 * ($max - $min);
    }

    /**
     * Generates a v4 random UUID (Universally Unique IDentifier)
     *
     * The version 4 UUID is purely random (except the version). It doesn't contain meaningful
     * information such as MAC address, time, etc. See RFC 4122 for details of UUID.
     *
     * This algorithm sets the version number (4 bits) as well as two reserved bits.
     * All other bits (the remaining 122 bits) are set using a random or pseudo-random data source.
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hexadecimal
     * digit and y is one of 8, 9, A, or B (e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479).
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->getUuid(); // 1378c906-64bb-4f81-a8d6-4ae1bfcdec22
     *</code>
     *
     * @link https://www.ietf.org/rfc/rfc4122.txt
     *
     * @return string
     */
    public function getUuid()
    {
        $bytes = unpack('N1a/n1b/n1c/n1d/n1e/N1f', $this->getByte(16));
        return sprintf('%08x-%04x-%04x-%04x-%04x%08x', $bytes['a'], $bytes['b'], ($bytes['c'] & 0x0FFF) | 0x4000, ($bytes['d'] & 0x3FFF) | 0x8000, $bytes['e'], $bytes['f']);
    }

    /**
     * https://en.wikipedia.org/wiki/Linear_congruential_generator
     *
     * @param int $n
     *
     * @return int
     */
    public function lgc($n)
    {
        return (1103515245 * $n) & 0x7FFFFFFF;
    }
}