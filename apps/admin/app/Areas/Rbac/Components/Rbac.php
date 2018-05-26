<?php

namespace App\Admin\Areas\Rbac\Components;

use App\Admin\Areas\Rbac\Models\Permission;
use App\Admin\Areas\Rbac\Models\RolePermission;
use App\Admin\Areas\Rbac\Models\AdminRole;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Component;
use App\Admin\Areas\Rbac\Components\Rbac\Exception as RbacException;

/**
 *
 * @package rbac
 *
 * @property \ManaPHP\Mvc\DispatcherInterface    $dispatcher
 * @property \ManaPHP\Security\IdentityInterface $identity
 */
class Rbac extends Component implements AuthorizationInterface
{
    /**
     * @param string $permissionName
     * @param int    $userId
     *
     * @return bool
     */
    public function isAllowed($permissionName, $userId = null)
    {
        $userId = $userId ?: $this->identity->getId();
        $permission = Permission::first(['path' => $permissionName]);

        if (!$permission) {
            throw new RbacException(['`:permission` permission is not exists'/**m06ab9af781c2de7f2*/, 'permission' => $permissionName]);
        }

        switch ($permission->type) {
            case Permission::TYPE_PENDING:
                throw new RbacException(['`:permission` type is not assigned'/**m0ac1449c071933ff6*/, 'permission' => $permission->description]);
            case Permission::TYPE_PUBLIC:
                return true;
            case Permission::TYPE_INTERNAL:
                return !empty($userId);
            case Permission::TYPE_DISABLED:
                return false;
            case Permission::TYPE_PRIVATE:
                $rolesByPermissionId = RolePermission::values('role_id', ['permission_id' => $permission->permission_id]);
                $rolesByUserId = AdminRole::values('role_id', ['admin_id' => $userId]);
                return (bool)array_intersect($rolesByPermissionId, $rolesByUserId);
            default:
                throw new RbacException(['`:permission` type is not recognized', 'permission' => $permissionName]);
        }
    }
}