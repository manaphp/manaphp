<?php

namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\RandomInterface
 *
 * @package random
 */
interface RandomInterface
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function getByte($length);

    /**
     * @param int $length
     * @param int $base
     *
     * @return string
     */
    public function getBase($length, $base = 62);

    /**
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public function getInt($min = 0, $max = 2147483647);

    /**
     * @param float $min
     * @param float $max
     *
     * @return float
     */
    public function getFloat($min = 0.0, $max = 1.0);

    /**
     * https://en.wikipedia.org/wiki/Linear_congruential_generator
     *
     * @param int $n
     *
     * @return int
     */
    public function lgc($n);
}