<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use ManaPHP\Utility\Text;

/**
 * Class RbacPermissionController
 *
 * @package App\Controllers
 *
 * @property \ManaPHP\Mvc\Application $application
 */
class PermissionController extends ControllerBase
{
    public function getAcl()
    {
        return ['list' => '@index'];
    }

    public function indexAction()
    {
        if ($this->request->isAjax()) {
            if ($permission_id = $this->request->get('permission_id', 'int', 0)) {
                return Permission::all(['permission_id' => $permission_id], ['with' => ['roles' => 'role_id, role_name']]);
            } else {
                return Permission::all([], ['with' => ['roles' => 'role_id, role_name']]);
            }
        }
    }

    public function listAction()
    {
        if ($this->request->isAjax()) {
            return Permission::all([], [], ['permission_id', 'path', 'description']);
        }
    }

    public function rebuildAction()
    {
        if ($this->request->isPost()) {
            $controllers = [];
            foreach ($this->filesystem->glob('@app/Controllers/*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            foreach ($this->filesystem->glob('@app/Areas/*/Controllers/*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            $count = 0;
            foreach ($controllers as $controller) {
                /**@var \ManaPHP\Controller $controllerInstance */
                $controllerInstance = new $controller;
                $acl = $controllerInstance->getAcl();

                if (preg_match('#/Areas/([^/]*)/Controllers/(.*)Controller$#', str_replace('\\', '/', $controller), $match)) {
                    $area = $match[1];
                    $controller_name = $match[2];
                } else {
                    $area = null;
                    $controller_name = basename(str_replace('\\', '/', $controller), 'Controller');
                }

                foreach (get_class_methods($controllerInstance) as $method) {
                    if ($method[0] === '_' || !preg_match('#^(.*)Action$#', $method, $match)) {
                        continue;
                    }

                    $action = $match[1];
                    if (isset($acl[$action])) {
                        $roles = $acl[$action];
                        if ($roles[0] === '@' || in_array($acl[$action], ['*', 'guest', 'user', 'admin'], true)) {
                            continue;
                        }
                    } else {
                        if (isset($acl['*']) && in_array($acl['*'], ['*', 'guest', 'user', 'admin'], true)) {
                            continue;
                        }
                    }
                    $path = '/' . ($area ? Text::underscore($area) . '/' : '') . Text::underscore($controller_name) . '/' . Text::underscore($action);
                    $path = preg_replace('#(/index)+$#', '', $path) ?: '/';

                    if (Permission::exists(['path' => $path])) {
                        continue;
                    }

                    $permission = new Permission();
                    $permission->path = $path;
                    $permission->description = $path;
                    $permission->create();
                    $count++;
                }
            }

            return $this->response->setJsonContent(['code' => 0, 'message' => "新增 $count 条"]);
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            return Permission::updateOrFail();
        }
    }
}