<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\RbacRole;

class RbacRoleController extends ControllerBase
{
    public function indexAction()
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('rr.*')
            ->addFrom(RbacRole::class, 'rr');

        $roles = $builder->execute();

        $this->view->setVars(compact('roles'));
    }

    public function createAction()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->get('name', 'trim');
            $description = $this->request->get('description', 'trim');

            if (RbacRole::exists(['role_name' => $name])) {
                return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'role exists']);
            }

            $rbacRole = new RbacRole();

            $rbacRole->role_name = $name;
            $rbacRole->description = $description;
            $rbacRole->enabled = 1;
            $rbacRole->created_time = time();

            $rbacRole->create();

            return $this->response->setJsonContent(['code' => 0, 'error' => '']);
        }
    }
}