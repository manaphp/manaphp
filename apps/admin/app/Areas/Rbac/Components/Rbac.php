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
     * @param string $permissionName
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permissionName = null, $role = null)
    {
        $role = $role ?: $this->identity->getRole();
        $permission = Permission::first(['path' => $permissionName]);

        if (!$permission) {
            throw new InvalidValueException(['`:permission` permission is not exists'/**m06ab9af781c2de7f2*/, 'permission' => $permissionName]);
        }

        switch ($permission->type) {
            case Permission::TYPE_PENDING:
                throw new InvalidValueException(['`:permission` type is not assigned'/**m0ac1449c071933ff6*/, 'permission' => $permission->description]);
            case Permission::TYPE_PUBLIC:
                return true;
            case Permission::TYPE_INTERNAL:
                return !empty($role);
            case Permission::TYPE_PRIVATE:
                $rolesByPermissionId = RolePermission::values('role_id', ['permission_id' => $permission->permission_id]);
                return Role::exists(['role_name' => $role, 'role_id' => $rolesByPermissionId]);
            default:
                throw new InvalidValueException(['`:permission` type is not recognized', 'permission' => $permissionName]);
        }
    }
}