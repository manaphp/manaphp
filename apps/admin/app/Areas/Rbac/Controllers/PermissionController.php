<?php

namespace App\Admin\Areas\Rbac\Controllers;

use App\Admin\Areas\Rbac\Models\Permission;
use ManaPHP\Utility\Text;

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
            if ($permission_id = $this->request->get('permission_id', 'int', 0)) {
                return Permission::find(['permission_id' => $permission_id], ['with' => ['roles' => 'role_id, role_name']]);
            } else {
                return Permission::find([], ['with' => ['roles' => 'role_id, role_name']]);
            }
        }
    }

    public function listAction()
    {
        if ($this->request->isAjax()) {
            return Permission::find([], [], ['permission_id', 'path', 'description']);
        }
    }

    public function rebuildAction()
    {
        if (!$this->request->isPost()) {
            return;
        }

        foreach ($this->filesystem->glob('@app/Controllers/*Controller.php') as $item) {
            $controller = $this->alias->resolveNS('@ns.app\\Controllers\\' . basename($item, '.php'));
            $controller_path = '/' . Text::underscore(basename($item, 'Controller.php'));

            foreach (get_class_methods($controller) as $method) {
                if ($method[0] === '_' || !preg_match('#^(.*)Action$#', $method, $match)) {
                    continue;
                }
                $path = preg_replace('#(/index)+$#', '', $controller_path . '/' . Text::underscore($match[1])) ?: '/';
                if (Permission::exists(['path' => $path])) {
                    continue;
                }

                $permission = new Permission();
                $permission->type = Permission::TYPE_PENDING;
                $permission->path = $path;
                $permission->description = $path;
                $permission->create();
            }
        }

        foreach ($this->filesystem->glob('@app/Areas/*', GLOB_ONLYDIR) as $area) {
            $area = basename($area);
            foreach (glob($this->alias->resolve("@app/Areas/$area/Controllers/*Controller.php")) as $item) {
                $controller = $this->alias->resolveNS("@ns.app\\Areas\\$area\\Controllers\\" . basename($item, '.php'));
                $controller_path = '/' . Text::underscore($area) . '/' . Text::underscore(basename($item, 'Controller.php'));

                foreach (get_class_methods($controller) as $method) {
                    if ($method[0] === '_' || !preg_match('#^(.*)Action$#', $method, $match)) {
                        continue;
                    }

                    $path = preg_replace('#(/index)+$#', '', $controller_path . '/' . Text::underscore($match[1])) ?: '/';
                    if (Permission::exists(['path' => $path])) {
                        continue;
                    }

                    $permission = new Permission();
                    $permission->type = Permission::TYPE_PENDING;
                    $permission->path = $path;
                    $permission->description = $path;
                    $permission->create();
                }
            }
        }

        return 0;
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            return Permission::updateOrFail();
        }
    }
}