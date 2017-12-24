<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\RbacPermission;
use Application\Admin\Models\RbacRolePermission;
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
        if ($this->request->isAjax()) {
            $permissions = [];
            foreach (RbacPermission::find(['app_name' => $this->application->getAppName()]) as $permission) {
                $permissions[] = array_merge($permission->toArray(),
                    ['roles' => RbacRolePermission::find(['permission_id' => $permission->permission_id], ['role_id', 'role_name'])]);
            }
            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $permissions]);
        }
    }

    public function listAction()
    {
        if ($this->request->isAjax()) {
            return $this->response->setJsonContent([
                'code' => 0,
                'message' => '',
                'data' => RbacPermission::criteria([
                    'permission_id',
                    'module_name',
                    'controller_name',
                    'action_name',
                    'description'
                ])->where(['app_name' => $this->application->getAppName()])->execute()
            ]);
        }
    }

    public function rebuildAction()
    {
        if ($this->request->isPost()) {
            $permissionBuilder = new PermissionBuilder();

            foreach ($permissionBuilder->getModules() as $module) {
                foreach ($permissionBuilder->getControllers($module) as $controller) {
                    $controllerName = basename($controller, 'Controller');
                    foreach ($permissionBuilder->getActions($controller) as $actionName => $actionDescription) {
                        if (!RbacPermission::exists(['module_name' => $module, 'controller_name' => $controllerName, 'action_name' => $actionName])) {
                            $permission = new RbacPermission();
                            $permission->app_name = $this->application->getAppName();
                            $permission->module_name = $module;
                            $permission->controller_name = $controllerName;
                            $permission->action_name = $actionName;
                            $permission->description = implode(':', [$module, $controllerName, $actionName]);
                            $permission->created_time = time();
                            $permission->permission_type = RbacPermission::TYPE_PENDING;

                            $permission->create();
                        }
                    }
                }
            }

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }
}