<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Role;
use App\Models\Admin;
use App\Areas\Rbac\Models\AdminRole;

class AdminRoleController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            try {
                $admin_id = $this->request->get('admin_id', 'int', 0);
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            return AdminRole::all(['admin_id' => $admin_id],
                ['with' => ['role', 'admins' => 'admin_id, admin_name']],
                ['id', 'admin_id', 'role_id', 'creator_name', 'created_time']);
        }
    }

    public function detailAction()
    {
        if ($this->request->isAjax()) {
            try {
                $admin_id = $this->request->get('admin_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            return $this->response->setJsonContent(AdminRole::all(['admin_id' => $admin_id]));
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $new_roles = $this->request->get('role_ids', '*');
            } catch (\Exception $e) {
                return $e;
            }
            $admin = Admin::firstOrFail();

            $old_roles = AdminRole::values('role_id', ['admin_id' => $admin->admin_id]);
            AdminRole::deleteAll(['role_id' => array_values(array_diff($old_roles, $new_roles))]);

            foreach (array_diff($new_roles, $old_roles) as $role_id) {
                $adminRole = new AdminRole();

                $adminRole->admin_id = $admin->admin_id;
                $adminRole->admin_name = $admin->admin_name;
                $adminRole->role_id = $role_id;
                $adminRole->role_name = Role::firstOrFail($role_id)->role_name;

                $adminRole->create();
            }

            return 0;
        }
    }
}