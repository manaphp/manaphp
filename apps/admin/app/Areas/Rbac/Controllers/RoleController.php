<?php

namespace App\Admin\Areas\Rbac\Controllers;

use App\Admin\Areas\Rbac\Models\Role;

class RoleController extends ControllerBase
{
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
            return Role::createOrFail();
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