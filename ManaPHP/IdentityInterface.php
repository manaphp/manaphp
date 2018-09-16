<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\IdentityInterface
 */
interface IdentityInterface
{
    /**
     * @return bool
     */
    public function authenticate();

    /**
     * @return bool
     */
    public function isGuest();

    /**
     * @param int $default
     *
     * @return int
     */
    public function getId($default = null);

    /**
     * @param string $default
     *
     * @return string
     */
    public function getName($default = null);

    /**
     * @param string     $claim
     * @param string|int $value
     *
     * @return static
     */
    public function setClaim($claim, $value);

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims);

    /**
     * @param string     $claim
     * @param string|int $default
     *
     * @return string|int
     */
    public function getClaim($claim, $default = null);

    /**
     * @return array
     */
    public function getClaims();

    /**
     * @param string $claim
     *
     * @return bool
     */
    public function hasClaim($claim);
}