<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\RbacPermission;
use Application\Admin\Models\RbacRole;
use Application\Admin\Models\RbacRolePermission;

class RbacRolePermissionController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => RbacRolePermission::find(['role_id' => $role_id])]);
        }
    }

    public function saveAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id');
                $permissions = $this->request->get('permissions');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            $role = RbacRole::findById($role_id);
            if (!$role) {
                return $this->response->setJsonContent(['code' => 2, 'message' => 'role not exists']);
            }

            $old_permissions = RbacRolePermission::findDistinctValues('permission_id', ['role_id' => $role_id]);

            RbacRolePermission::deleteAll(['role_id' => $role_id, 'permission_id' => array_values(array_diff($old_permissions, $permissions))]);

            foreach (array_diff($permissions, $old_permissions) as $permission_id) {
                $rolePermission = new RbacRolePermission();
                $rolePermission->role_id = $role_id;
                $rolePermission->role_name = $role->role_name;
                $rolePermission->permission_id = $permission_id;
                $rolePermission->permission_description = RbacPermission::findValue(['permission_id' => $permission_id], 'description');
                $rolePermission->creator_id = $this->userIdentity->getId();
                $rolePermission->creator_name = $this->userIdentity->getName();
                $rolePermission->created_time = time();

                $rolePermission->create();
            }

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }
}