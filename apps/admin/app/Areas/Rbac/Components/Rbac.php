<?php

namespace App\Areas\Rbac\Components;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use ManaPHP\Authorization;
use ManaPHP\Exception\InvalidValueException;

/**
 *
 * @package rbac
 */
class Rbac extends Authorization
{
    /**
     * @param string $permission
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permission = null, $role = null)
    {
        $role = $role ?: $this->identity->getRole();
        $permissionModel = Permission::first(['path' => $permission]);

        if (!$permissionModel) {
            throw new InvalidValueException(['`:permission` permission is not exists'/**m06ab9af781c2de7f2*/, 'permission' => $permission]);
        }

        $rolesByPermissionId = RolePermission::values('role_id', ['permission_id' => $permissionModel->permission_id]);
        return Role::exists(['role_name' => $role, 'role_id' => $rolesByPermissionId]);
    }
}