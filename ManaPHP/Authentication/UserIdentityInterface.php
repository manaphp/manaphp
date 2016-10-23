<?php
namespace ManaPHP\Authentication;

/**
 * Interface ManaPHP\Authentication\UserIdentityInterface
 *
 * @package ManaPHP\Authentication
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