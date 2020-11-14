<?php

namespace ManaPHP;

interface JwtInterface
{
    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $secret
     *
     * @return string
     */
    public function encode($claims, $ttl, $secret = null);

    /**
     * @param string       $token
     * @param bool         $verify
     * @param string|array $secrets
     *
     * @return array
     */
    public function decode($token, $verify = true, $secrets = null);

    /**
     * @param string       $token
     * @param string|array $secrets
     *
     * @return void
     */
    public function verify($token, $secrets = null);

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     */
    public function scopedEncode($claims, $ttl, $scope);

    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     */
    public function scopedDecode($token, $scope, $verify = true);

    /**
     * @param string $token
     * @param string $scope
     *
     * @return void
     */
    public function scopedVerify($token, $scope);
}