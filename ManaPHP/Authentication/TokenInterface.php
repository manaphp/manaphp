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
     * @return array
     */
    public function encode($claims);

    /**
     * @param string $str
     *
     * @return array|false
     */
    public function decode($str);

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