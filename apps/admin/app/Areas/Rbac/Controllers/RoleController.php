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
                ->paginate()
            : null;
    }

    public function listAction()
    {
        return Role::lists([], ['role_id' => 'role_name']);
    }

    public function createAction()
    {
        return Role::createOrNull();
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
}