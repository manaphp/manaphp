<?php

namespace ManaPHP\Security;

use ManaPHP\Component;

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

        return random_bytes($length);
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