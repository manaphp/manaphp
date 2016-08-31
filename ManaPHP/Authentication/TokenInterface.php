<?php
namespace ManaPHP\Authentication;

interface TokenInterface
{
    /**
     * @param int $ttl
     *
     * @return string
     */
    public function encode($ttl = null);

    /**
     * @param string $str
     *
     * @return static
     */
    public function decode($str);

    /**
     * @return int
     */
    public function getExpiredTime();
}