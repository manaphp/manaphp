<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Entities\AdminRole;
use App\Areas\Rbac\Repositories\AdminRoleRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Controllers\Controller;
use App\Repositories\AdminRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;

#[Authorize('@index')]
#[RequestMapping('/rbac/admin-role')]
class AdminRoleController extends Controller
{
    #[Autowired] protected AdminRepository $adminRepository;
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected AdminRoleRepository $adminRoleRepository;

    #[ViewGetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        $fields = ['admin_id', 'admin_name', 'created_time',
                   'roles' => ['role_id', 'display_name']
        ];

        $orders = ['admin_id' => SORT_DESC];

        $restrictions = Restrictions::create();
        $restrictions->contains('admin_name', $keyword);

        return $this->adminRepository->paginate($restrictions, $fields, $orders, Page::of($page, $size));
    }

    #[GetMapping]
    public function detailAction(int $admin_id)
    {
        return $this->adminRoleRepository->all(['admin_id' => $admin_id]);
    }

    #[PostMapping]
    public function editAction(int $admin_id, array $role_ids = [])
    {
        $admin = $this->adminRepository->get($admin_id);
        $old_roles = $this->adminRoleRepository->values('role_id', ['admin_id' => $admin->admin_id]);
        $this->adminRoleRepository->deleteAll(['role_id' => array_values(array_diff($old_roles, $role_ids))]);

        foreach (array_diff($role_ids, $old_roles) as $role_id) {
            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role_id;
            $adminRole->role_name = $this->roleRepository->value(['role_id' => $role_id], 'role_name');

            $this->adminRoleRepository->create($adminRole);
        }
    }
}