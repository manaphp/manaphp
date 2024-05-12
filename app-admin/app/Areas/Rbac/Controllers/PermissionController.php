<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\Permission;
use App\Areas\Rbac\Models\Role;
use App\Areas\Rbac\Repositories\PermissionRepository;
use App\Areas\Rbac\Repositories\RolePermissionRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\ControllersInterface;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Restrictions;

#[Authorize('@index')]
#[RequestMapping('/rbac/permission')]
class PermissionController extends Controller
{
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected ControllersInterface $controllers;
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected PermissionRepository $permissionRepository;
    #[Autowired] protected RolePermissionRepository $rolePermissionRepository;

    #[ViewGetMapping('')]
    public function indexAction()
    {
        return Permission::select()
            ->where(Restrictions::of($this->request->all(), ['permission_id']))
            ->with(['roles' => ['role_id', 'display_name']])
            ->orderBy(['permission_id' => SORT_DESC]);
    }

    #[GetMapping]
    public function listAction()
    {
        $fields = ['permission_id', 'handler', 'display_name'];
        $orders = ['handler' => SORT_ASC];
        return $this->permissionRepository->all([], $fields, $orders);
    }

    #[GetMapping]
    public function rebuildAction()
    {
        $count = 0;
        foreach ($this->controllers->getControllers() as $controller) {
            foreach ($this->authorization->getPermissions($controller) as $handler) {
                if ($this->permissionRepository->exists(['handler' => $handler])) {
                    continue;
                }

                $permission = new Permission();

                $permission->handler = $handler;
                $permission->display_name = $handler;

                $this->permissionRepository->create($permission);

                $count++;
            }
        }

        foreach (['guest', 'user', 'admin'] as $role_name) {
            if (!$this->roleRepository->exists(['role_name' => $role_name])) {
                $role = new Role();

                $role->role_name = $role_name;
                $role->display_name = $role_name;
                $role->enabled = 1;
                $role->permissions = '';

                $this->roleRepository->create($role);
            }
        }

        foreach ($this->roleRepository->all() as $role) {
            $permission_ids = $this->rolePermissionRepository->values('permission_id', ['role_id' => $role->role_id]);
            $granted = $this->permissionRepository->values('handler', ['permission_id' => $permission_ids]);
            $role_permissions = $this->authorization->buildAllowed($role->role_name, $granted);
            $role->permissions = ',' . implode(',', $role_permissions) . ',';
            $this->roleRepository->update($role);
        }

        return ['code' => 0, 'message' => "新增 $count 条"];
    }

    #[PostMapping]
    public function editAction()
    {
        return $this->permissionRepository->update($this->request->all());
    }

    #[PostMapping]
    public function deleteAction(int $permission_id)
    {
        $this->rolePermissionRepository->deleteAll(['permission_id' => $permission_id]);

        return $this->permissionRepository->deleteById($permission_id);
    }
}