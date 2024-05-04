<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\InputInterface;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\View;
use ManaPHP\Query\QueryInterface;

#[Authorize('@index')]
#[RequestMapping('/rbac/admin')]
class AdminController extends Controller
{
    #[View]
    #[GetMapping('')]
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
        return Admin::kvalues('admin_name');
    }

    #[PostMapping]
    public function lockAction(int $admin_id)
    {
        $admin = Admin::get($admin_id);

        if ($this->identity->getId() === $admin->admin_id) {
            return '不能锁定自己';
        }

        $admin->status = Admin::STATUS_LOCKED;

        return $admin->update();
    }

    #[PostMapping]
    public function activeAction(int $admin_id)
    {
        $admin = Admin::get($admin_id);

        $admin->status = Admin::STATUS_ACTIVE;

        return $admin->update();
    }

    #[PostMapping]
    public function createAction(InputInterface $input, ?int $role_id)
    {
        $admin = Admin::fillCreate($input->all());

        if ($role_id) {
            $role = Role::get($role_id);

            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;
            $adminRole->create();
        }

        return $admin;
    }

    #[PostMapping]
    public function editAction(int $admin_id, array $role_ids = [], string $password = '')
    {
        $admin = Admin::get($admin_id);

        $admin->assign($this->request->all(), ['email', 'white_ip']);

        if ($password !== '') {
            $admin->password = $password;
        }

        $admin->update();

        $old_role_ids = AdminRole::values('role_id', ['admin_id' => $admin->admin_id]);
        foreach (array_diff($old_role_ids, $role_ids) as $role_id) {
            AdminRole::firstOrFail(['admin_id' => $admin->admin_id, 'role_id' => $role_id])->delete();
        }

        foreach (array_diff($role_ids, $old_role_ids) as $role_id) {
            $role = Role::get($role_id);
            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role->role_id;
            $adminRole->role_name = $role->role_name;

            $adminRole->create();
        }

        return $admin;
    }

    #[GetMapping]
    public function rolesAction()
    {
        return Role::lists(['display_name', 'role_name'], ['role_name!=' => ['guest', 'user']]);
    }
}