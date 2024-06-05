<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Entities\RolePermission;
use App\Areas\Rbac\Repositories\PermissionRepository;
use App\Areas\Rbac\Repositories\RolePermissionRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Areas\Rbac\Services\RoleService;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Restrictions;
use function explode;
use function str_contains;
use function trim;

#[Authorize]
#[RequestMapping('/rbac/role-permission')]
class RolePermissionController extends Controller
{
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected PermissionRepository $permissionRepository;
    #[Autowired] protected RolePermissionRepository $rolePermissionRepository;
    #[Autowired] protected RoleService $roleService;

    #[ViewGetMapping]
    public function indexAction(int $role_id = 0)
    {
        if ($role_id > 0) {
            $fields = ['permission_id', 'handler', 'display_name', 'created_time'];
            $role = $this->roleRepository->get($role_id);

            $restrictions = Restrictions::create();
            $restrictions->in('handler', explode(',', trim($role->permissions)));

            $permissions = $this->permissionRepository->all($restrictions, $fields);

            $roles = $this->roleRepository->all();
            foreach ($permissions as $permission) {
                foreach ($roles as $role) {
                    if (str_contains($role->permissions, $permission->handler)) {
                        $permission->roles[] = $role;
                    }
                }
            }
            return $permissions;
        } else {
            return [];
        }
    }

    #[PostMapping]
    public function editAction(int $role_id, array $permission_ids = [])
    {
        $role = $this->roleRepository->get($role_id);
        $old_permissions = $this->rolePermissionRepository->values('permission_id', ['role_id' => $role->role_id]);

        $this->rolePermissionRepository->deleteAll(
            ['role_id'       => $role->role_id,
             'permission_id' => array_values(array_diff($old_permissions, $permission_ids))]
        );

        foreach (array_diff($permission_ids, $old_permissions) as $permission_id) {
            $rolePermission = new RolePermission();
            $rolePermission->role_id = $role->role_id;
            $rolePermission->permission_id = $permission_id;
            $this->rolePermissionRepository->create($rolePermission);
        }

        $granted = $this->roleService->getGrantedPermissions($role_id);
        $permissions = $this->roleService->getPermissions($role->role_name, $granted);

        $role->permissions = ',' . implode(',', $permissions) . ',';
        $this->roleRepository->update($role);
    }

    #[GetMapping]
    public function permissionsAction()
    {
        $fields = ['permission_id', 'handler', 'display_name'];
        $orders = ['handler' => SORT_ASC];
        return $this->permissionRepository->all(['grantable' => 1], $fields, $orders);
    }

    #[GetMapping]
    public function rolesAction()
    {
        return $this->roleRepository->all([], ['role_id', 'role_name', 'display_name']);
    }

    #[GetMapping]
    public function grantedAction(int $role_id)
    {
        return $this->rolePermissionRepository->values('permission_id', ['role_id' => $role_id]);
    }
}