<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Models\Admin;
use ManaPHP\Mvc\Controller;
use ManaPHP\QueryInterface;

class AdminController extends Controller
{
    public function getVerbs()
    {
        return array_merge(parent::getVerbs(), [
            'roles' => 'GET'
        ]);
    }

    public function indexAction()
    {
        return Admin::select(['admin_id', 'admin_name', 'status', 'login_ip', 'login_time', 'email', 'updator_name', 'creator_name', 'created_time', 'updated_time'])
            ->orderBy(['admin_id' => SORT_DESC])
            ->with(['roles' => 'role_id, display_name'])
            ->when(static function (QueryInterface $query) {
                $keyword = input('keyword', '');
                if (strpos($keyword, '@') !== false) {
                    $query->whereContains('email', $keyword);
                } else {
                    $query->whereContains(['admin_name', 'email'], $keyword);
                }
            })->paginate();
    }

    public function listAction()
    {
        return Admin::lists(['admin_id' => 'admin_name']);
    }

    public function lockAction()
    {
        if ($this->identity->getId() == input('admin_id')) {
            return '不能锁定自己';
        }

        return Admin::rUpdate(['status' => Admin::STATUS_LOCKED]);
    }

    public function activeAction()
    {
        return Admin::rUpdate(['status' => Admin::STATUS_ACTIVE]);
    }

    public function createAction($role_id)
    {
        $admin = Admin::rCreate();

        if ($role_id) {
            $role = Role::get($role_id);

            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;
            $adminRole->create();
        }

        return $admin;
    }

    public function editAction($role_ids = [])
    {
        $admin = Admin::rGet();

        $admin->load(['email']);
        if ($password = input('password', '')) {
            $admin->password = $password;
        }
        $admin->update();

        $old_role_ids = AdminRole::values('role_id', ['admin_id' => $admin->admin_id]);
        foreach (array_diff($old_role_ids, $role_ids) as $role_id) {
            AdminRole::firstOrFail(['admin_id' => $admin->admin_id, 'role_id' => $role_id])->delete();
        }

        foreach (array_diff($role_ids, $old_role_ids) as $role_id) {
            $role = Role::get($role_id);
            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;

            $adminRole->create();
        }

        return $admin;
    }

    public function rolesAction()
    {
        return Role::lists(['display_name', 'role_name'], ['role_name!=' => ['guest', 'user']]);
    }
}