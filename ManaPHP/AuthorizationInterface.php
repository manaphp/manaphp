<?php
namespace ManaPHP;

interface AuthorizationInterface
{
    /**
     * Check whether a user is allowed to access a permission
     *
     * @param string $permission
     * @param string $userId
     *
     * @return boolean
     */
    public function isAllowed($permission, $userId = null);
}