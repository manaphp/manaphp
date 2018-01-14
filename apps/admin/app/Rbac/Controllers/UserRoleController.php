<?php
namespace App\Admin\Rbac\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Rbac\Models\Role;
use ManaPHP\Authorization\Rbac\Models\UserRole;

class UserRoleController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            try {
                $role_id = $this->request->get('role_id', 'int', 0);
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            $criteria = Admin::criteria(['admin_id', 'admin_name', 'email', 'created_time'])
                ->whereRequest(['admin_name*=']);
            if ($role_id) {
                $criteria->whereIn('admin_id', UserRole::findList(['role_id' => $role_id], 'user_id'));
            }

            $criteria->paginate(15);
            foreach ($this->paginator->items as $k => $user) {
                $this->paginator->items[$k]['roles'] = UserRole::findList(['user_id' => $user['admin_id']], ['role_id' => 'role_name']);
            }

            return $this->response->setJsonContent($this->paginator);
        }
    }

    public function detailAction()
    {
        if ($this->request->isAjax()) {
            try {
                $user_id = $this->request->get('user_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            return $this->response->setJsonContent(UserRole::find(['user_id' => $user_id]));
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $user_id = $this->request->get('user_id', '*|int');
                $new_roles = $this->request->get('role_ids', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            $adminUser = Admin::firstOrFail($user_id);
			
            $old_roles = UserRole::findDistinctValues('role_id', ['user_id' => $user_id]);
            UserRole::deleteAll(['role_id' => array_values(array_diff($old_roles, $new_roles))]);

            foreach (array_diff($new_roles, $old_roles) as $role_id) {
                $userRole = new UserRole();

                $userRole->user_id = $adminUser->admin_id;
                $userRole->user_name = $adminUser->admin_name;
                $userRole->role_id = $role_id;
                $userRole->role_name = Role::firstOrFail($role_id)->role_name;
                $userRole->creator_id = $this->userIdentity->getId();
                $userRole->creator_name = $this->userIdentity->getName();
                $userRole->created_time = time();

                $userRole->create();
            }

            return $this->response->setJsonContent(0);
        }
    }
}