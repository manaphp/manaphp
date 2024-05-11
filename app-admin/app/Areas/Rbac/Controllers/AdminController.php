<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Repositories\AdminRoleRepository;
use App\Areas\Rbac\Repositories\RoleRepository;
use App\Controllers\Controller;
use App\Models\Admin;
use App\Repositories\AdminRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\InputInterface;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Query\QueryInterface;

#[Authorize('@index')]
#[RequestMapping('/rbac/admin')]
class AdminController extends Controller
{
    #[Autowired] protected AdminRepository $adminRepository;
    #[Autowired] protected RoleRepository $roleRepository;
    #[Autowired] protected AdminRoleRepository $adminRoleRepository;

    #[ViewGetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        return Admin::select(
            ['admin_id', 'admin_name', 'status', 'white_ip', 'login_ip', 'login_time', 'email', 'updator_name',
             'creator_name', 'created_time', 'updated_time']
        )
            ->orderBy(['admin_id' => SORT_DESC])
            ->with(['roles' => 'role_id, display_name'])
            ->callable(
                static function (QueryInterface $query) use ($keyword) {
                    if (str_contains($keyword, '@')) {
                        $query->whereContains('email', $keyword);
                    } else {
                        $query->whereContains(['admin_name', 'email'], $keyword);
                    }
                }
            )->paginate($page, $size);
    }

    #[GetMapping]
    public function listAction()
    {
        return $this->adminRepository->kvalues('admin_name');
    }

    #[PostMapping]
    public function lockAction(int $admin_id)
    {
        $admin = $this->adminRepository->get($admin_id);

        if ($this->identity->getId() === $admin->admin_id) {
            return '不能锁定自己';
        }

        $admin->status = Admin::STATUS_LOCKED;

        return $this->adminRepository->update($admin);
    }

    #[PostMapping]
    public function activeAction(int $admin_id)
    {
        $admin = $this->adminRepository->get($admin_id);

        $admin->status = Admin::STATUS_ACTIVE;

        return $this->adminRepository->update($admin);
    }

    #[PostMapping]
    public function createAction(InputInterface $input, ?int $role_id)
    {
        $admin = $this->adminRepository->create($input->all());

        if ($role_id) {
            $role = $this->roleRepository->get($role_id);

            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;

            $this->adminRoleRepository->create($adminRole);
        }

        return $admin;
    }

    #[PostMapping]
    public function editAction(int $admin_id, array $role_ids = [], string $password = '')
    {
        $admin = $this->adminRepository->get($admin_id);

        $admin->assign($this->request->all(), ['email', 'white_ip']);

        if ($password !== '') {
            $admin->password = $password;
        }

        $this->adminRepository->update($admin);

        $old_role_ids = $this->adminRoleRepository->values('role_id', ['admin_id' => $admin->admin_id]);
        foreach (array_diff($old_role_ids, $role_ids) as $role_id) {
            $adminRole = $this->adminRoleRepository->firstOrFail(['admin_id' => $admin->admin_id, 'role_id' => $role_id]
            );
            $this->adminRoleRepository->delete($adminRole);
        }

        foreach (array_diff($role_ids, $old_role_ids) as $role_id) {
            $role = $this->roleRepository->get($role_id);
            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;

            $this->adminRoleRepository->create($adminRole);
        }

        return $admin;
    }

    #[GetMapping]
    public function rolesAction()
    {
        return $this->roleRepository->lists(['display_name', 'role_name'], ['role_name!=' => ['guest', 'user']]);
    }
}