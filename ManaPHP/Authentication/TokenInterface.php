<?php
namespace ManaPHP\Authentication;

/**
 * Interface ManaPHP\Authentication\TokenInterface
 *
 * @package token
 */
interface TokenInterface
{
    /**
     *
     * @return string
     */
    public function encode();

    /**
     * @param string $str
     *
     * @return static
     */
    public function decode($str);
}