<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Repositories\AdminRoleRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;

#[Authorize('@index')]
#[RequestMapping('/rbac/role')]
class RoleController extends Controller
{
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected AdminRoleRepository $adminRoleRepository;

    #[ViewGetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        $restrictions = Restrictions::create()
            ->contains(['role_name', 'display_name'], $keyword)
            ->nin('role_name', ['guest', 'user', 'admin']);

        $orders = ['role_id' => SORT_DESC];

        return $this->roleRepository->paginate([], $restrictions, $orders, Page::of($page, $size));
    }

    #[GetMapping]
    public function listAction()
    {
        return $this->roleRepository->lists(['display_name', 'role_name']);
    }

    #[PostMapping]
    public function createAction(string $role_name)
    {
        $permissions = ',' . implode(',', $this->authorization->buildAllowed($role_name)) . ',';

        $role = $this->roleRepository->fill($this->request->all());
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
        $role = $this->roleRepository->get($role_id);
        $role->enabled = 0;

        return $this->roleRepository->update($role);
    }

    #[PostMapping]
    public function enableAction(int $role_id)
    {
        $role = $this->roleRepository->get($role_id);
        $role->enabled = 1;

        return $this->roleRepository->update($role);
    }

    #[PostMapping]
    public function deleteAction(int $role_id)
    {
        if ($this->adminRoleRepository->exists(['role_id' => $role_id])) {
            return '删除失败: 有用户绑定到此角色';
        }

        return $this->roleRepository->deleteById($role_id);
    }
}