<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\View;

#[Authorize('@index')]
#[RequestMapping('/menu/group')]
class GroupController extends Controller
{
    #[View]
    #[GetMapping('')]
    public function indexAction()
    {
        return Group::select()
            ->whereCriteria($this->request->all(), ['group_id'])
            ->orderBy(['display_order' => SORT_DESC, 'group_id' => SORT_ASC])
            ->all();
    }

    #[GetMapping]
    public function listAction()
    {
        return Group::all([], ['group_id', 'group_name']);
    }

    #[PostMapping]
    public function createAction()
    {
        return Group::fillCreate($this->request->all());
    }

    #[PostMapping]
    public function editAction(Group $group)
    {
        return $group->fillUpdate($this->request->all());
    }

    #[PostMapping]
    public function deleteAction(Group $group)
    {
        return $group->delete();
    }
}