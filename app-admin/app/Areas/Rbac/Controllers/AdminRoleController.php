<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class AdminRoleController extends Controller
{
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        return Admin::select(['admin_id', 'admin_name', 'created_time'])
            ->orderBy(['admin_id' => SORT_DESC])
            ->where(['admin_name*=' => $keyword])
            ->with(['roles' => 'role_id, display_name'])
            ->paginate($page, $size);
    }

    public function detailAction(int $admin_id)
    {
        return AdminRole::all(['admin_id' => $admin_id]);
    }

    public function editAction(Admin $admin, array $role_ids = [])
    {
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