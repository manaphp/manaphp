<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

/**
 * Class RbacPermissionController
 *
 * @package App\Controllers
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 * @property-read \ManaPHP\Http\Controller\ManagerInterface $controllerManager
 *
 */
#[Authorize('@index')]
class PermissionController extends Controller
{
    public function indexAction()
    {
        return Permission::all(
            ['permission_id?' => input('permission_id', '')],
            ['with' => ['roles' => 'role_id, display_name'], 'order' => 'permission_id DESC']
        );
    }

    public function listAction()
    {
        return Permission::all([], ['order' => 'path'], ['permission_id', 'path', 'display_name']);
    }

    public function rebuildAction()
    {
        $count = 0;
        foreach ($this->controllerManager->getControllers() as $controller) {
            foreach ($this->authorization->getPermissions($controller) as $path) {
                if (Permission::exists(['path' => $path])) {
                    continue;
                }

                $permission = new Permission();
                $permission->path = $path;
                $permission->display_name = $path;
                $permission->create();

                $count++;
            }
        }

        foreach (['guest', 'user', 'admin'] as $role_name) {
            if (!Role::exists(['role_name' => $role_name])) {
                $role = new Role();
                $role->role_name = $role_name;
                $role->display_name = $role_name;
                $role->enabled = true;
                $role->permissions = '';
                $role->create();
            }
        }

        foreach (Role::all() as $role) {
            $permission_ids = RolePermission::values('permission_id', ['role_id' => $role->role_id]);
            $granted = Permission::values('path', ['permission_id' => $permission_ids]);
            $role_permissions = $this->authorization->buildAllowed($role->role_name, $granted);
            $role->permissions = ',' . implode(',', $role_permissions) . ',';
            $role->update();
        }

        return ['code' => 0, 'message' => "新增 $count 条"];
    }

    public function editAction()
    {
        return Permission::rUpdate();
    }

    public function deleteAction()
    {
        $permission = Permission::rGet();

        RolePermission::deleteAll(['permission_id' => $permission->permission_id]);

        return $permission->delete();
    }
}