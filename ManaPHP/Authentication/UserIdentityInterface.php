<?php
namespace ManaPHP\Authentication;

/**
 * Interface ManaPHP\Authentication\UserIdentityInterface
 *
 * @package userIdentity
 */
interface UserIdentityInterface
{
    /**
     * @return bool
     */
    public function isGuest();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return int
     */
    public function getRoleId();

    /**
     * @return string
     */
    public function getRoleName();
}