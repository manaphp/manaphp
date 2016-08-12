<?php
namespace Application\Admin\Controllers;

use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\Mvc\Controller;

class RbacPermissionController extends Controller{
    public function indexAction(){
        $items=$this->modelsManager->createBuilder()
                ->columns('*')
                ->addFrom(Permission::class)
                ->execute();

        $this->view->setVar('items',$items);
    }
}