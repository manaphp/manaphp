<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class RoleController extends Controller
{
    public function indexAction()
    {
        return Role::select()
            ->whereContains('role_name', input('keyword', ''))
            ->whereNotIn('role_name', ['guest', 'user', 'admin'])
            ->orderBy(['role_id' => SORT_DESC])
            ->paginate();
    }

    public function listAction()
    {
        return Role::lists(['display_name', 'role_name']);
    }

    public function createAction()
    {
        if ($role_name = input('role_name', '')) {
            $permissions = ',' . implode(',', $this->authorization->buildAllowed($role_name)) . ',';
        } else {
            $permissions = '';
        }

        return Role::fillCreate($this->request->all(), ['permissions' => $permissions]);
    }

    public function editAction(Role $role)
    {
        return $role->fillUpdate($this->request->all());
    }

    public function disableAction(Role $role)
    {
        return $role->update(['enabled' => 0]);
    }

    public function enableAction(Role $role)
    {
        return $role->update(['enabled' => 1]);
    }

    public function deleteAction(Role $role)
    {
        if (AdminRole::exists(['role_id' => $role->role_id])) {
            return '删除失败: 有用户绑定到此角色';
        }

        return $role->delete();
    }
}