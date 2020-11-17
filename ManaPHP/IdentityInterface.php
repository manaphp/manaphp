<?php

namespace ManaPHP;

interface IdentityInterface
{
    /**
     * @return static
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
     * @param string $default
     *
     * @return string
     */
    public function getRole($default = 'guest');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isRole($name);

    /**
     * @param string $role
     *
     * @return static
     */
    public function setRole($role);

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setClaim($name, $value);

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims);

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getClaim($name, $default = null);

    /**
     * @return array
     */
    public function getClaims();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasClaim($name);
}