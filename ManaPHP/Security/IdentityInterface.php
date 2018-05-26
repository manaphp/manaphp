<?php

namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\IdentityInterface
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
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

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
    public function hasClaims($claim);
}