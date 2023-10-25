<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class RolePermissionController extends Controller
{
    #[Autowired] protected AuthorizationInterface $authorization;

    public function indexAction()
    {
        return RolePermission::select(['id', 'permission_id', 'creator_name', 'created_time'])
            ->with(['permission' => 'permission_id, display_name, handler', 'roles' => 'role_id, role_name, display_name'])
            ->whereCriteria($this->request->all(), ['role_id'])
            ->all();
    }

    public function saveAction(Role $role, array $permission_ids = [])
    {
        $old_permissions = RolePermission::values('permission_id', ['role_id' => $role->role_id]);

        RolePermission::deleteAll(
            ['role_id'       => $role->role_id,
             'permission_id' => array_values(array_diff($old_permissions, $permission_ids))]
        );

        foreach (array_diff($permission_ids, $old_permissions) as $permission_id) {
            $rolePermission = new RolePermission();
            $rolePermission->role_id = $role->role_id;
            $rolePermission->permission_id = $permission_id;
            $rolePermission->create();
        }

        $explicit_permissions = Permission::values('handler', ['permission_id' => $permission_ids]);
        $handlers = $this->authorization->buildAllowed($role->role_name, $explicit_permissions);
        sort($handlers);

        $role->permissions = ',' . implode(',', $handlers) . ',';
        $role->update();
    }

    public function editAction(Role $role, array $permission_ids)
    {
        $this->saveAction($role, $permission_ids);
    }
}