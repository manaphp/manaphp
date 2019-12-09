<?php

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Models\RolePermission;
use ManaPHP\Helper\Str;
use ManaPHP\Mvc\Controller;

/**
 * Class RbacPermissionController
 *
 * @package App\Controllers
 * @property-read \ManaPHP\AuthorizationInterface $authorization
 *
 */
class PermissionController extends Controller
{
    public function indexAction()
    {
        return Permission::all(['permission_id?' => input('permission_id', '')], ['with' => ['roles' => 'role_id, role_name, display_name'], 'order' => 'permission_id DESC']);
    }

    public function listAction()
    {
        return Permission::all([], [], ['permission_id', 'path', 'display_name']);
    }

    public function rebuildAction()
    {
        $controllers = $this->aclBuilder->getControllers();
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

            $actions = $this->aclBuilder->getActions($controller);

            if (isset($acl['*']) && $acl['*'] === '@index' && !in_array('index', $actions, true)) {
                $actions[] = 'index';
            }

            foreach ($acl as $ac) {
                if ($ac[0] === '@') {
                    $original_action = substr($ac, 1);
                    if (!in_array($original_action, $actions, true)) {
                        return sprintf('invalid original action: `%s` controller does not exist `%sAction` method', $controller, $original_action);
                    }
                }
            }

            foreach ($actions as $action) {
                if (isset($acl[$action]) || (isset($acl['*']) && $acl['*'] !== "@$action")) {
                    continue;
                }

                $path = '/' . ($area ? Str::underscore($area) . '/' : '') . Str::underscore($controller_name) . '/' . Str::underscore($action);
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

        foreach (['guest', 'user', 'admin'] as $role_name) {
            if (!Role::exists(['role_name' => $role_name])) {
                $role = new Role();
                $role->role_name = $role_name;
                $role->display_name = $role_name;
                $role->permissions = '';
                $role->create();
            }
        }

        foreach (Role::all() as $role) {
            if ($role->role_name !== 'admin') {
                $permission_ids = RolePermission::values('permission_id', ['role_id' => $role->role_id]);
                $permissions = Permission::values('path', ['permission_id' => $permission_ids]);
                $role_permissions = $this->authorization->buildAllowed($role->role_name, $permissions);
                $role->permissions = ',' . implode(',', $role_permissions) . ',';
                $role->update();
            }
        }

        return ['code' => 0, 'message' => "新增 $count 条"];
    }

    public function editAction()
    {
        return Permission::rUpdate();
    }

    public function deleteAction()
    {
        $permission = Permission::rGet();
        foreach (Role::all(['role_id' => RolePermission::values('role_id', ['permission_id' => $permission->permission_id])]) as $role) {
            if (strpos($role->permissions, ",$permission->path,") !== false) {
                $role->permissions = str_replace(",$permission->path,", ',', $role->permissions);
                $role->update();
            }
        }

        RolePermission::deleteAll(['permission_id' => $permission->permission_id]);

        return $permission->delete();
    }
}