<?php
namespace ManaPHP\Authentication;

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