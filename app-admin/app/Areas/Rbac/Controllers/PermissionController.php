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
use ManaPHP\Http\ControllersInterface;

#[Authorize('@index')]
class PermissionController extends Controller
{
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected ControllersInterface $controllers;

    public function indexAction()
    {
        return Permission::select()
            ->whereCriteria($this->request->all(), ['permission_id'])
            ->with(['roles' => 'role_id, display_name'])
            ->orderBy(['permission_id' => SORT_DESC]);
    }

    public function listAction()
    {
        return Permission::select(['permission_id', 'handler', 'display_name'])->orderBy(['handler' => SORT_ASC]);
    }

    public function rebuildAction()
    {
        $count = 0;
        foreach ($this->controllers->getControllers() as $controller) {
            foreach ($this->authorization->getPermissions($controller) as $handler) {
                if (Permission::exists(['handler' => $handler])) {
                    continue;
                }

                $permission = new Permission();
                $permission->handler = $handler;
                $permission->display_name = $handler;
                $permission->create();

                $count++;
            }
        }

        foreach (['guest', 'user', 'admin'] as $role_name) {
            if (!Role::exists(['role_name' => $role_name])) {
                $role = new Role();
                $role->role_name = $role_name;
                $role->display_name = $role_name;
                $role->enabled = 1;
                $role->permissions = '';
                $role->create();
            }
        }

        foreach (Role::all() as $role) {
            $permission_ids = RolePermission::values('permission_id', ['role_id' => $role->role_id]);
            $granted = Permission::values('handler', ['permission_id' => $permission_ids]);
            $role_permissions = $this->authorization->buildAllowed($role->role_name, $granted);
            $role->permissions = ',' . implode(',', $role_permissions) . ',';
            $role->update();
        }

        return ['code' => 0, 'message' => "新增 $count 条"];
    }

    public function editAction(Permission $permission)
    {
        return $permission->fillUpdate($this->request->all());
    }

    public function deleteAction(Permission $permission)
    {
        RolePermission::deleteAll(['permission_id' => $permission->permission_id]);

        return $permission->delete();
    }
}