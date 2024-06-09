<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Services;

use App\Areas\Rbac\Entities\Role;
use App\Areas\Rbac\Repositories\PermissionRepository;
use App\Areas\Rbac\Repositories\RolePermissionRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use function explode;
use function in_array;
use function sort;
use function str_ends_with;

class RoleService
{
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected RolePermissionRepository $rolePermissionRepository;
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected PermissionRepository $permissionRepository;

    public function ensureBuiltinExists(): void
    {
        foreach ([Authorize::GUEST, Authorize::USER, Authorize::ADMIN] as $role_name) {
            if (!$this->roleRepository->exists(['role_name' => $role_name])) {
                $role = new Role();

                $role->role_name = $role_name;
                $role->display_name = $role_name;
                $role->enabled = 1;
                $role->permissions = '';
                $role->builtin = 1;

                $this->roleRepository->create($role);
            }
        }
    }

    protected function getPermissionsInternal(array $controllerPermissions, array $actionPermissions, string $role,
        array $granted
    ): array {
        $permissions = [];
        foreach ($actionPermissions as $permission_code => $permission) {
            if ($permission->authorize === '' && !$permission->grantable) {
                list($controller,) = explode('::', $permission_code, 2);
                $permission = $controllerPermissions[$controller . '::*'] ?? null;
            }

            if ($permission === null) {
                SuppressWarnings::noop();
            } else {
                if ($permission->grantable) {
                    if (in_array($permission->permission_code, $granted, true)) {
                        $permissions[] = $permission_code;
                    }
                } else {
                    if ($permission->authorize === $role) {
                        $permissions[] = $permission_code;
                    }
                }
            }
        }

        sort($permissions);
        return $permissions;
    }

    public function getPermissions(string $role, array $granted): array
    {
        $controllerPermissions = [];
        $actionPermissions = [];
        foreach ($this->permissionRepository->all() as $permission) {
            $permission_code = $permission->permission_code;
            if (str_ends_with($permission_code, '*')) {
                $controllerPermissions[$permission_code] = $permission;
            } else {
                $actionPermissions[$permission_code] = $permission;
            }
        }

        return $this->getPermissionsInternal($controllerPermissions, $actionPermissions, $role, $granted);
    }

    public function getGrantedPermissions(int $role_id): array
    {
        $permission_ids = $this->rolePermissionRepository->values('permission_id', ['role_id' => $role_id]);
        return $this->permissionRepository->values('permission_code', ['permission_id' => $permission_ids]);
    }

    public function rebuildPermissions(): void
    {
        $controllerPermissions = [];
        $actionPermissions = [];
        foreach ($this->permissionRepository->all() as $permission) {
            $permission_code = $permission->permission_code;
            if (str_ends_with($permission_code, '*')) {
                $controllerPermissions[$permission_code] = $permission;
            } else {
                $actionPermissions[$permission_code] = $permission;
            }
        }

        foreach ($this->roleRepository->all() as $role) {
            $granted = $this->getGrantedPermissions($role->role_id);
            $permissions = $this->getPermissionsInternal(
                $controllerPermissions, $actionPermissions, $role->role_name, $granted
            );
            $role->permissions = implode(',', $permissions);

            $this->roleRepository->update($role);
        }
    }
}