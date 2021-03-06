<?php

namespace ManaPHP\Token;

interface ScopedJwtInterface
{
    /**
     * @param string $scope
     *
     * @return string
     */
    public function getKey($scope);

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     */
    public function encode($claims, $ttl, $scope);

    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     */
    public function decode($token, $scope, $verify = true);

    /**
     * @param string $token
     * @param string $scope
     *
     * @return void
     */
    public function verify($token, $scope);
}