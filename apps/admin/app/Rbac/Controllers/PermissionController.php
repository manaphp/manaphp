<?php
namespace App\Admin\Rbac\Controllers;

use App\Admin\Rbac\Models\Permission;
use App\Admin\Rbac\Models\RolePermission;
use ManaPHP\Authorization\Rbac\PermissionBuilder;

/**
 * Class RbacPermissionController
 *
 * @package App\Admin\Controllers
 *
 * @property \ManaPHP\Mvc\Application $application
 */
class PermissionController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $permissions = [];
            foreach (Permission::find(['app_name' => $this->application->getAppName()]) as $permission) {
                $permissions[] = array_merge($permission->toArray(),
                    ['roles' => RolePermission::find(['permission_id' => $permission->permission_id], ['role_id', 'role_name'])]);
            }
            return $this->response->setJsonContent($permissions);
        }
    }

    public function listAction()
    {
        if ($this->request->isAjax()) {
            return $this->response->setJsonContent(
                Permission::criteria([
                    'permission_id',
                    'module_name',
                    'controller_name',
                    'action_name',
                    'description'
                ])->where(['app_name' => $this->application->getAppName()])->indexBy('permission_id')->execute()
            );
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
                        if (!Permission::exists(['module_name' => $module, 'controller_name' => $controllerName, 'action_name' => $actionName])) {
                            $permission = new Permission();

                            $permission->permission_type = Permission::TYPE_PENDING;
                            $permission->app_name = $this->application->getAppName();
                            $permission->module_name = $module;
                            $permission->controller_name = $controllerName;
                            $permission->action_name = $actionName;
                            $permission->description = implode(':', [$module, $controllerName, $actionName]);

                            $permission->create();
                        }
                    }
                }
            }

            return $this->response->setJsonContent(0);
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $permission_id = $this->request->get('permission_id', '*|int');
                $permission_type = $this->request->get('type', '*|int');
                $description = $this->request->get('description', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            $permission = Permission::firstOrFail($permission_id);

            $permission->description = $description;
            $permission->permission_type = $permission_type;

            $permission->update();

            return $this->response->setJsonContent(0);
        }
    }
}