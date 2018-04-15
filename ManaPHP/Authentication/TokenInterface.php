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
     * @param array $claims
     *
     * @return string
     */
    public function encode($claims);

    /**
     * @param string $token
     *
     * @return array|false
     */
    public function decode($token);

    /**
     * @param string $claim
     *
     * @return string|int
     */
    public function getClaim($claim);

    /**
     * @param string $claim
     *
     * @return bool
     */
    public function hasClaim($claim);
}