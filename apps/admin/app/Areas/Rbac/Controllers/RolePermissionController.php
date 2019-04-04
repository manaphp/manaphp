<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use ManaPHP\Mvc\Controller;

/**
 * Class RolePermission
 * @package App\Areas\Rbac\Models
 *
 * @property-read \ManaPHP\AuthorizationInterface $authorization
 */
class RolePermissionController extends Controller
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            return RolePermission::all(['role_id' => input('role_id')],
                ['with' => ['permission' => 'permission_id, display_name, path', 'roles' => 'role_id, role_name']],
                ['id', 'permission_id', 'creator_name', 'created_time']);
        }
    }

    public function saveAction()
    {
        if ($this->request->isPost()) {

            $role_id = input('role_id');
            $permission_ids = input('permission_ids', []);

            $old_permissions = RolePermission::values('permission_id', ['role_id' => $role_id]);

            RolePermission::deleteAll(['role_id' => $role_id, 'permission_id' => array_values(array_diff($old_permissions, $permission_ids))]);

            foreach (array_diff($permission_ids, $old_permissions) as $permission_id) {
                $rolePermission = new RolePermission();
                $rolePermission->role_id = $role_id;
                $rolePermission->permission_id = $permission_id;
                $rolePermission->create();
            }

            $role = Role::get($role_id);

            $paths = $this->authorization->getAllowed($role->role_name);
            sort($paths);

            $role->permissions = ',' . implode(',', $paths) . ',';
            $role->update();

            return 0;
        }
    }
}