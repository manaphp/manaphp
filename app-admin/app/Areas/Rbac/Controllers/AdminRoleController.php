<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\View;

#[Authorize('@index')]
#[RequestMapping('/rbac/admin-role')]
class AdminRoleController extends Controller
{
    #[View]
    #[GetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        return Admin::select(['admin_id', 'admin_name', 'created_time'])
            ->orderBy(['admin_id' => SORT_DESC])
            ->whereContains(['admin_name'], $keyword)
            ->with(['roles' => 'role_id, display_name'])
            ->paginate($page, $size);
    }

    #[GetMapping]
    public function detailAction(int $admin_id)
    {
        return AdminRole::all(['admin_id' => $admin_id]);
    }

    #[PostMapping]
    public function editAction(int $admin_id, array $role_ids = [])
    {
        $admin = Admin::get($admin_id);
        $old_roles = AdminRole::values('role_id', ['admin_id' => $admin->admin_id]);
        AdminRole::deleteAll(['role_id' => array_values(array_diff($old_roles, $role_ids))]);

        foreach (array_diff($role_ids, $old_roles) as $role_id) {
            $adminRole = new AdminRole();

            $adminRole->admin_id = $admin->admin_id;
            $adminRole->admin_name = $admin->admin_name;
            $adminRole->role_id = $role_id;
            $adminRole->role_name = Role::value(['role_id' => $role_id], 'role_name');

            $adminRole->create();
        }
    }
}