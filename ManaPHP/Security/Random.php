<?php
namespace ManaPHP\Security;

/**
 * Class ManaPHP\Security\Random
 *
 * @package random
 */
class Random implements RandomInterface
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function getByte($length)
    {
        if (function_exists('random_bytes')) {
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
}