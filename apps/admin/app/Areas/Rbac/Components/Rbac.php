<?php

namespace App\Areas\Rbac\Components;

use App\Areas\Rbac\Models\Role;
use ManaPHP\Authorization;

/**
 *
 * @package rbac
 */
class Rbac extends Authorization
{
    /**
     * @param string $role
     *
     * @return string
     */
    public function getAllowed($role)
    {
        return Role::value(['role_name' => $role], 'permissions');
    }
}