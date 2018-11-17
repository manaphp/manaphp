<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Role;

class RoleController extends ControllerBase
{
    public function getAcl()
    {
        return ['list' => '@index'];
    }

    public function indexAction()
    {
        if ($this->request->isAjax()) {
            return Role::query()
                ->whereContains('role_name', $this->request->get('keyword'))
                ->orderBy('role_id desc')
                ->paginate(15);
        }
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
        if ($this->request->isPost()) {
            return Role::updateOrFail();
        }
    }

    public function disableAction()
    {
        if ($this->request->isPost()) {
            return Role::updateOrFail(['enabled' => 0]);
        }
    }

    public function enableAction()
    {
        if ($this->request->isPost()) {
            return Role::updateOrFail(['enabled' => 1]);
        }
    }
}