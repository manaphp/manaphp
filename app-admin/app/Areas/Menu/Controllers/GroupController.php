<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class GroupController extends Controller
{
    public function indexAction()
    {
        return Group::search(['group_id'])
            ->orderBy(['display_order' => SORT_DESC, 'group_id' => SORT_ASC])
            ->all();
    }

    public function listAction()
    {
        return Group::all([], null, ['group_id', 'group_name']);
    }

    public function createAction(Group $group)
    {
        return $group->create();
    }

    public function editAction(Group $group)
    {
        return $group->update();
    }

    public function deleteAction(Group $group)
    {
        return $group->delete();
    }
}