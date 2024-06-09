<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Services;

use App\Areas\Rbac\Entities\Permission;
use App\Areas\Rbac\Repositories\PermissionRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\ControllersInterface;
use ManaPHP\Http\Router\Attribute\Mapping;
use ManaPHP\Http\Router\Attribute\MappingInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use function implode;

class PermissionService
{
    #[Autowired] protected ControllersInterface $controllers;
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected PermissionRepository $permissionRepository;

    public function rebuild(): array
    {
        $oldPermissions = [];

        foreach ($this->permissionRepository->all() as $permission) {
            $oldPermissions[$permission->permission_code] = $permission;
        }

        $newPermissions = [];
        foreach ($this->controllers->getControllers() as $controller) {
            $rClass = new ReflectionClass($controller);

            if (($attribute = $rClass->getAttributes(Authorize::class)[0] ?? null) !== null) {
                /** @var Authorize $controllerAuthorize */
                $controllerAuthorize = $attribute->newInstance();

                $permission = new Permission();

                $permission->permission_code = $this->authorization->getPermission($controller, '*');
                $permission->display_name = $controller . '::*';
                $permission->authorize = implode(',', $controllerAuthorize->roles);
                $permission->grantable = (int)$controllerAuthorize->isGrantable();
                $newPermissions[$permission->permission_code] = $permission;
            } else {
                $controllerAuthorize = null;
            }

            foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
                if ($rMethod->getAttributes(MappingInterface::class, ReflectionAttribute::IS_INSTANCEOF) === []) {
                    continue;
                }
                $action = $rMethod->getName();

                $permission = new Permission();

                $permission->permission_code = $this->authorization->getPermission($controller, $action);
                $permission->display_name = $controller . '::' . $action;

                if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) !== null) {
                    /** @var Authorize $actionAuthorize */
                    $actionAuthorize = $attribute->newInstance();

                    $permission->authorize = implode(',', $actionAuthorize->roles);
                    $permission->grantable = (int)$actionAuthorize->isGrantable();
                } else {
                    $permission->authorize = '';
                    $permission->grantable = $controllerAuthorize ? 0 : 1;
                }

                $newPermissions[$permission->permission_code] = $permission;
            }
        }

        $deletedCount = 0;
        foreach ($oldPermissions as $permission_code => $permission) {
            if (!isset($newPermissions[$permission_code])) {
                $deletedCount++;
                $this->permissionRepository->delete($permission);
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        foreach ($newPermissions as $permission_code => $permission) {
            if (($oldPermission = $oldPermissions[$permission_code] ?? null) === null) {
                $createdCount++;
                $this->permissionRepository->create($permission);
            } else {
                $diff = false;
                foreach (['permission_code', 'display_name', 'authorize', 'grantable'] as $field) {
                    if ($permission->$field !== $oldPermission->$field) {
                        $diff = true;
                        break;
                    }
                }

                if ($diff) {
                    $updatedCount++;
                    $permission->permission_id = $oldPermission->permission_id;
                    $this->permissionRepository->update($permission);
                }
            }
        }

        return ['create' => $createdCount, 'update' => $updatedCount, 'delete' => $deletedCount];
    }
}