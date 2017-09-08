<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\RbacPermission;
use ManaPHP\Authorization\Rbac\PermissionBuilder;

/**
 * Class RbacPermissionController
 *
 * @package Application\Admin\Controllers
 *
 * @property \ManaPHP\Mvc\Application $application
 */
class RbacPermissionController extends ControllerBase
{
    public function indexAction()
    {
        $this->view->setLayout();

        $permission_groups = [];
        foreach (RbacPermission::createQuery()->execute() as $row) {
            $group = $row['module'] . ':' . $row['controller'];

            if (!isset($permissions[$group])) {
                $permissions[$group] = [];
            }

            $permission_groups[$group][] = $row;
        }

        ksort($permission_groups);

        $this->view->setVars(compact('permission_groups'));
    }

    public function rebuildAction()
    {
        $permissionBuilder = new PermissionBuilder();

        foreach ($this->application->getModules() as $module) {
            foreach ($permissionBuilder->getModulePermissions($module) as $p) {
                $permission = RbacPermission::findFirst(['module' => $p['module'], 'controller' => $p['controller'], 'action' => $p['action']]);
                if ($permission === false) {
                    $permission = new RbacPermission();

                    $permission->module = $p['module'];
                    $permission->controller = $p['controller'];
                    $permission->action = $p['action'];
                    $permission->description = $p['description'];
                    $permission->created_time = time();
                    $permission->permission_type = RbacPermission::TYPE_PENDING;

                    $permission->create();
                }
            }
        }

        return $this->response->redirect('/rbac_permission');
    }
}