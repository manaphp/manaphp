<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use ManaPHP\Mvc\Controller;
use ManaPHP\Utility\Text;

/**
 * Class RbacPermissionController
 *
 * @package App\Controllers
 *
 */
class PermissionController extends Controller
{
    public function getAcl()
    {
        return ['list' => '@index'];
    }

    public function indexAction()
    {
        return $this->request->isAjax()
            ? Permission::all(['permission_id?' => input('permission_id', '')], ['with' => ['roles' => 'role_id, role_name']])
            : null;
    }

    public function listAction()
    {
        return $this->request->isAjax() ? Permission::all([], [], ['permission_id', 'path', 'display_name']) : null;
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
                /**@var \ManaPHP\Rest\Controller $controllerInstance */
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
                    $permission->display_name = $path;
                    $permission->create();
                    $count++;
                }
            }

            return ['code' => 0, 'message' => "新增 $count 条"];
        }
    }

    public function editAction()
    {
        return Permission::updateOrNull();
    }
}