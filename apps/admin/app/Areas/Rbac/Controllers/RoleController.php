<?php

namespace App\Areas\Rbac\Controllers;

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
                ->paginate(15)
            : null;
    }

    public function listAction()
    {
        return Role::lists([], ['role_id' => 'role_name']);
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            $role = Role::newOrFail();
            $role->permissions = '';
            return $role->create();
        }
    }

    public function editAction()
    {
        return $this->request->isPost() ? Role::updateOrFail() : null;
    }

    public function disableAction()
    {
        return $this->request->isPost() ? Role::updateOrFail(['enabled' => 0]) : null;
    }

    public function enableAction()
    {
        return $this->request->isPost() ? Role::updateOrFail(['enabled' => 1]) : null;
    }
}