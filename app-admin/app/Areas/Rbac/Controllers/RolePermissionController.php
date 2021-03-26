<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use App\Controllers\Controller;

/**
 * Class RolePermission
 *
 * @package App\Areas\Rbac\Models
 *
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class RolePermissionController extends Controller
{
    public function indexAction()
    {
        return RolePermission::select(['id', 'permission_id', 'creator_name', 'created_time'])
            ->with(['permission' => 'permission_id, display_name, path', 'roles' => 'role_id, role_name, display_name'])
            ->search(['role_id'])
            ->all();
    }

    public function saveAction()
    {
        $role_id = input('role_id');
        $permission_ids = input('permission_ids', []);

        $old_permissions = RolePermission::values('permission_id', ['role_id' => $role_id]);

        RolePermission::deleteAll(
            ['role_id' => $role_id, 'permission_id' => array_values(array_diff($old_permissions, $permission_ids))]
        );

        foreach (array_diff($permission_ids, $old_permissions) as $permission_id) {
            $rolePermission = new RolePermission();
            $rolePermission->role_id = $role_id;
            $rolePermission->permission_id = $permission_id;
            $rolePermission->create();
        }

        $role = Role::get($role_id);

        $explicit_permissions = Permission::values('path', ['permission_id' => $permission_ids]);
        $paths = $this->authorization->buildAllowed($role->role_name, $explicit_permissions);
        sort($paths);

        $role->permissions = ',' . implode(',', $paths) . ',';
        $role->update();
    }

    public function editAction()
    {
        return $this->saveAction();
    }
}