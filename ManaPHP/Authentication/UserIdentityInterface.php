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