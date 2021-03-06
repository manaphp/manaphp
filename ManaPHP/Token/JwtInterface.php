<?php

namespace ManaPHP\Token;

interface JwtInterface
{
    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $key
     *
     * @return string
     */
    public function encode($claims, $ttl, $key = null);

    /**
     * @param string $token
     * @param bool   $verify
     * @param string $key
     *
     * @return array
     */
    public function decode($token, $verify = true, $key = null);

    /**
     * @param string $token
     * @param string $key
     *
     * @return void
     */
    public function verify($token, $key = null);
}