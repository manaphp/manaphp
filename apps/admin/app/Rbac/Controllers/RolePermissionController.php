<?php
namespace App\Admin\Rbac\Controllers;

use App\Admin\Rbac\Models\Permission;
use App\Admin\Rbac\Models\Role;
use App\Admin\Rbac\Models\RolePermission;

class RolePermissionController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e->getMessage());
            }

            return $this->response->setJsonContent(RolePermission::find(['role_id' => $role_id]));
        }
    }

    public function saveAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id');
                $permissions = $this->request->get('permissions');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e->getMessage());
            }

            $role = Role::findById($role_id);
            if (!$role) {
                return $this->response->setJsonContent('role not exists');
            }

            $old_permissions = RolePermission::findDistinctValues('permission_id', ['role_id' => $role_id]);

            RolePermission::deleteAll(['role_id' => $role_id, 'permission_id' => array_values(array_diff($old_permissions, $permissions))]);

            foreach (array_diff($permissions, $old_permissions) as $permission_id) {
                $rolePermission = new RolePermission();
                $rolePermission->role_id = $role_id;
                $rolePermission->role_name = $role->role_name;
                $rolePermission->permission_id = $permission_id;
                $rolePermission->permission_description = Permission::findValue(['permission_id' => $permission_id], 'description');
                $rolePermission->creator_id = $this->userIdentity->getId();
                $rolePermission->creator_name = $this->userIdentity->getName();
                $rolePermission->created_time = time();

                $rolePermission->create();
            }

            return $this->response->setJsonContent(0);
        }
    }
}