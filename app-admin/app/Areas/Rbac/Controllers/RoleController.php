<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize('@index')]
#[RequestMapping('/rbac/role')]
class RoleController extends Controller
{
    #[ViewGetMapping('')]
    public function indexAction(string $keyword = '', int $page = 1, int $size = 10)
    {
        return Role::select()
            ->whereContains(['role_name', 'display_name'], $keyword)
            ->whereNotIn('role_name', ['guest', 'user', 'admin'])
            ->orderBy(['role_id' => SORT_DESC])
            ->paginate($page, $size);
    }

    #[GetMapping]
    public function listAction()
    {
        return Role::lists(['display_name', 'role_name']);
    }

    #[PostMapping]
    public function createAction(string $role_name)
    {
        $permissions = ',' . implode(',', $this->authorization->buildAllowed($role_name)) . ',';

        return Role::fillCreate($this->request->all(), ['permissions' => $permissions]);
    }

    #[PostMapping]
    public function editAction(Role $role)
    {
        return $role->fillUpdate($this->request->all());
    }

    #[PostMapping]
    public function disableAction(Role $role)
    {
        $role->enabled = 0;

        return $role->update();
    }

    #[PostMapping]
    public function enableAction(Role $role)
    {
        $role->enabled = 1;

        return $role->update();
    }

    #[PostMapping]
    public function deleteAction(Role $role)
    {
        if (AdminRole::exists(['role_id' => $role->role_id])) {
            return '删除失败: 有用户绑定到此角色';
        }

        return $role->delete();
    }
}