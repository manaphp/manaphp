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
     * @param string $role
     *
     * @return array
     */
    public function getAllowed($role)
    {
        $role_id = Role::value(['role_name' => $role], 'role_id');
        if ($role_id) {
            $permission_ids = RolePermission::values('permission_id', ['role_id' => $role_id]);
            $paths_db = array_flip(Permission::values('path', ['permission_id' => $permission_ids]));
        } else {
            $paths_db = [];
        }

        $paths_acl = array_flip(parent::getAllowed($role));

        return array_keys($paths_db + $paths_acl);
    }

    /**
     * @param string $permission
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permission = null, $role = null)
    {
        $role = $role ?: $this->identity->getRole();
        if ($role === 'admin') {
            return true;
        }

        if ($permission && strpos($permission, '/') !== false) {
            list($controllerClassName, $action) = $this->inferControllerAction($permission);
            $controllerClassName = $this->alias->resolveNS($controllerClassName);
            if (!isset($this->_acl[$controllerClassName])) {
                /** @var \ManaPHP\Controller $controllerInstance */
                $controllerInstance = new $controllerClassName;
                $this->_acl[$controllerClassName] = $controllerInstance->getAcl();
            }
        } else {
            $controllerInstance = $this->dispatcher->getControllerInstance();
            $controllerClassName = get_class($controllerInstance);
            $action = $permission ?: $this->dispatcher->getAction();

            if (!isset($this->_acl[$controllerClassName])) {
                $this->_acl[$controllerClassName] = $controllerInstance->getAcl();
            }
        }

        $acl = $this->_acl[$controllerClassName];

        foreach (explode(',', $role) as $r) {
            if ($this->isAclAllow($acl, $r, $action)) {
                return true;
            }
        }

        if (isset($acl[$action])) {
            return false;
        }

        if (!$permission) {
            $permission = $this->generatePath($controllerClassName, isset($acl[$action]) ? substr($acl[$action], 1) : $action);
        }
        $permissionModel = Permission::first(['path' => $permission]);

        if (!$permissionModel) {
            throw new InvalidValueException(['`:permission` permission is not exists'/**m06ab9af781c2de7f2*/, 'permission' => $permission]);
        }

        $rolesByPermissionId = RolePermission::values('role_id', ['permission_id' => $permissionModel->permission_id]);
        return Role::exists(['role_name' => explode(',', $role), 'role_id' => $rolesByPermissionId]);
    }
}