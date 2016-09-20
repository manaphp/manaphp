<?php
namespace Application\Admin\Controllers;

use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\Authorization\Rbac\PermissionBuilder;
use ManaPHP\Mvc\Controller;

/**
 * Class RbacPermissionController
 *
 * @package Application\Admin\Controllers
 *
 * @property \ManaPHP\Mvc\Application $application
 */
class RbacPermissionController extends Controller
{
    public function indexAction()
    {
        $this->view->setLayout();
        $items = $this->modelsManager->createBuilder()
            ->columns('*')
            ->addFrom(Permission::class)
            ->execute();

        $data = ['permissions' => $items];
        $this->view->setVar('data', $data);
    }

    public function rebuildAction()
    {
        $permissionBuilder = new PermissionBuilder();

        foreach ($this->application->getModules() as $module) {
            foreach ($permissionBuilder->getModulePermissions($module) as $p) {
                $permission = Permission::findFirst(['module' => $p['module'], 'controller' => $p['controller'], 'action' => $p['action']]);
                if ($permission === false) {
                    $permission = new Permission();

                    $permission->module = $p['module'];
                    $permission->controller = $p['controller'];
                    $permission->action = $p['action'];
                    $permission->description = $p['description'];
                    $permission->created_time = time();
                    $permission->permission_type = Permission::TYPE_PENDING;

                    $permission->create();
                }
            }
        }

        return $this->response->redirect('/admin/rbac_permission');
    }
}