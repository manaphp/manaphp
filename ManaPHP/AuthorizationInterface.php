<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\AuthorizationInterface
 *
 * @package ManaPHP
 */
interface AuthorizationInterface
{
    /**
     * Check whether a user is allowed to access a permission
     *
     * @param string $permission
     * @param string $userId
     *
     * @return bool
     */
    public function isAllowed($permission, $userId = null);
}