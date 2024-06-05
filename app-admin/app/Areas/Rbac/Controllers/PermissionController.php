<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Repositories\PermissionRepository;
use App\Areas\Rbac\Repositories\RolePermissionRepository;
use App\Areas\Rbac\Services\PermissionService;
use App\Areas\Rbac\Services\RoleService;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Restrictions;

#[Authorize]
#[RequestMapping('/rbac/permission')]
class PermissionController extends Controller
{
    #[Autowired] protected PermissionRepository $permissionRepository;
    #[Autowired] protected RolePermissionRepository $rolePermissionRepository;
    #[Autowired] protected PermissionService $permissionService;
    #[Autowired] protected RoleService $roleService;

    #[ViewGetMapping]
    public function indexAction()
    {
        $fields = ['roles' => ['role_id', 'display_name']];
        $restrictions = Restrictions::of($this->request->all(), ['permission_id']);
        $orders = ['permission_id' => SORT_DESC];

        return $this->permissionRepository->all($restrictions, $fields, $orders);
    }

    #[GetMapping]
    public function listAction()
    {
        $fields = ['permission_id', 'handler', 'display_name'];
        $orders = ['handler' => SORT_ASC];
        return $this->permissionRepository->all([], $fields, $orders);
    }

    #[PostMapping]
    public function rebuildAction()
    {
        $counts = $this->permissionService->rebuild();

        $this->roleService->ensureBuiltinExists();
        $this->roleService->rebuildPermissions();

        $createdCount = $counts['create'];
        $updatedCount = $counts['update'];
        $deletedCount = $counts['delete'];

        return $this->response->json(
            ['code' => 0, 'msg' => "新增 $createdCount 条，删除$deletedCount 条, 更新 $updatedCount 条"]
        );
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