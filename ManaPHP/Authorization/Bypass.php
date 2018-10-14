<?php
namespace ManaPHP\Authorization;

use ManaPHP\AuthorizationInterface;

/**
 * Class ManaPHP\Authorization\Bypass
 *
 * @package ManaPHP\Authorization
 */
class Bypass implements AuthorizationInterface
{
    /**
     * @param string $permission
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permission, $role = null)
    {
        return true;
    }
}