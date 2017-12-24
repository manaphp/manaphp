<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\RbacRole;

class RbacRoleController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $query = RbacRole::query();
            $keyword = $this->request->get('keyword', 'trim');
            if ($keyword) {
                $query->whereContains('role_name', $keyword);
            }
            $query->whereRequest(['role_id', 'role_name'])
                ->orderBy('role_id desc');
            $query->paginate(15);
            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $this->paginator]);
        }
    }

    public function listAction()
    {
        return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => RbacRole::findList([], ['role_id' => 'role_name'])]);
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_name = $this->request->get('role_name', '*');
                $enabled = $this->request->get('enabled', 'int', 1);
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            if (RbacRole::exists(['role_name' => $role_name])) {
                return $this->response->setJsonContent(['code' => 2, 'message' => 'role exists']);
            }

            $rbacRole = new RbacRole();

            $rbacRole->role_name = $role_name;
            $rbacRole->enabled = $enabled;
            $rbacRole->creator_id = $this->userIdentity->getId();
            $rbacRole->creator_name = $this->userIdentity->getName();
            $rbacRole->updated_time = $rbacRole->created_time = time();

            $rbacRole->create();

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
                $role_name = $this->request->get('role_name', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            $rbacRole = RbacRole::findById($role_id);
            if (!$rbacRole) {
                return $this->response->setJsonContent(['code' => 2, 'message' => 'role not exists']);
            }

            if ($rbacRole->role_name !== $role_name) {
                if (RbacRole::exists(['role_name' => $role_name])) {
                    return $this->response->setJsonContent(['code' => 3, 'message' => 'role name is exists']);
                }
                $rbacRole->role_name = $role_name;
                $rbacRole->updated_time = time();
                $rbacRole->update();
            }

            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $rbacRole]);
        }
    }

    public function disableAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }
            $rbacRole = RbacRole::findById($role_id);
            if (!$rbacRole) {
                return $this->response->setJsonContent(['code' => 2, 'message' => 'role is not exists']);
            }

            $rbacRole->enabled = 0;
            $rbacRole->updated_time = time();

            $rbacRole->update();

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }

    public function enableAction()
    {
        if ($this->request->isPost()) {
            try {
                $role_id = $this->request->get('role_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }
            $rbacRole = RbacRole::findById($role_id);
            if (!$rbacRole) {
                return $this->response->setJsonContent(['code' => 2, 'message' => 'role is not exists']);
            }

            $rbacRole->enabled = 1;
            $rbacRole->updated_time = time();

            $rbacRole->update();

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }
}