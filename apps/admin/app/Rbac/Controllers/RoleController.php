<?php
namespace App\Admin\Rbac\Controllers;

use App\Admin\Rbac\Models\Role;

class RoleController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $query = Role::query();
            $keyword = $this->request->get('keyword', 'trim');
            if ($keyword) {
                $query->whereContains('role_name', $keyword);
            }
            $query->whereRequest(['role_id', 'role_name'])
                ->orderBy('role_id desc');
            $query->paginate(15);
            return $this->response->setJsonContent($this->paginator);
        }
    }

    public function listAction()
    {
        return $this->response->setJsonContent(Role::lists([], ['role_id' => 'role_name']));
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_name = $this->request->get('role_name', '*');
                $enabled = $this->request->get('enabled', 'int', 1);
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            if (Role::exists(['role_name' => $role_name])) {
                return $this->response->setJsonContent('role exists');
            }

            $rbacRole = new Role();

            $rbacRole->role_name = $role_name;
            $rbacRole->enabled = $enabled;
            $rbacRole->creator_id = $this->userIdentity->getId();
            $rbacRole->creator_name = $this->userIdentity->getName();

            $rbacRole->create();

            return $this->response->setJsonContent(0);
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
                $role_name = $this->request->get('role_name', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            $rbacRole = Role::firstOrFail($role_id);
			
            if ($rbacRole->role_name !== $role_name) {
                if (Role::exists(['role_name' => $role_name])) {
                    return $this->response->setJsonContent('role name is exists');
                }
                $rbacRole->role_name = $role_name;
                $rbacRole->update();
            }

            return $this->response->setJsonContent($rbacRole);
        }
    }

    public function disableAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            $rbacRole = Role::firstOrFail($role_id);
			
            $rbacRole->enabled = 0;

            $rbacRole->update();

            return $this->response->setJsonContent(0);
        }
    }

    public function enableAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            $rbacRole = Role::firstOrFail($role_id);
			
            $rbacRole->enabled = 1;

            $rbacRole->update();

            return $this->response->setJsonContent(0);
        }
    }
}