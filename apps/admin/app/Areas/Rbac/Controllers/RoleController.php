<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use ManaPHP\Mvc\Controller;

class RoleController extends Controller
{
    public function getAcl()
    {
        return ['list' => '@index'];
    }

    public function indexAction()
    {
        return $this->request->isAjax()
            ? Role::query()
                ->whereContains('role_name', input('keyword', ''))
                ->orderBy('role_id desc')
                ->paginate()
            : null;
    }

    public function listAction()
    {
        return Role::lists(['role_id' => 'role_name']);
    }

    public function createAction()
    {
        return Role::createOrNull(['permissions' => '']);
    }

    public function editAction()
    {
        return Role::updateOrNull();
    }

    public function disableAction()
    {
        return Role::updateOrNull(['enabled' => 0]);
    }

    public function enableAction()
    {
        return Role::updateOrNull(['enabled' => 1]);
    }

    public function deleteAction()
    {
        if (!$this->request->isGet()) {
            $role = Role::get(input('role_id'));

            if (AdminRole::exists(['role_id' => $role->role_id])) {
                return '删除失败: 有用户绑定到此角色';
            }

            return $role->delete();
        }
    }
}