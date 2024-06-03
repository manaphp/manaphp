<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Entities\Role;
use App\Areas\Rbac\Repositories\AdminRoleRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Areas\Rbac\Services\RoleService;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;
use function implode;

#[Authorize]
#[RequestMapping('/rbac/role')]
class RoleController extends Controller
{
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected AdminRoleRepository $adminRoleRepository;
    #[Autowired] protected RoleService $roleService;

    #[ViewGetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        $restrictions = Restrictions::create()
            ->contains(['role_name', 'display_name'], $keyword);

        $orders = ['role_id' => SORT_DESC];

        return $this->roleRepository->paginate($restrictions, [], $orders, Page::of($page, $size));
    }

    #[GetMapping]
    public function listAction()
    {
        return $this->roleRepository->all([], ['role_id', 'display_name']);
    }

    #[PostMapping]
    public function createAction(string $role_name)
    {
        $permissions = ',' . implode(',', $this->roleService->getPermissions($role_name, [])) . ',';

        $role = $this->roleRepository->fill($this->request->all());
        $role->builtin = 0;
        $role->permissions = $permissions;
        return $this->roleRepository->create($role);
    }

    #[PostMapping]
    public function editAction()
    {
        return $this->roleRepository->update($this->request->all());
    }

    #[PostMapping]
    public function disableAction(int $role_id)
    {
        $role = new Role();

        $role->role_id = $role_id;
        $role->enabled = 0;

        return $this->roleRepository->update($role);
    }

    #[PostMapping]
    public function enableAction(int $role_id)
    {
        $role = new Role();

        $role->role_id = $role_id;
        $role->enabled = 1;

        return $this->roleRepository->update($role);
    }

    #[GetMapping]
    public function detailAction(int $role_id)
    {
        return $this->roleRepository->first(['role_id' => $role_id]);
    }

    #[PostMapping]
    public function deleteAction(int $role_id)
    {
        if ($this->adminRoleRepository->exists(['role_id' => $role_id])) {
            return '删除失败: 有用户绑定到此角色';
        }

        $role = $this->roleRepository->get($role_id);
        if ($role->builtin) {
            return '内置角色不能删除';
        }

        return $this->roleRepository->delete($role);
    }
}