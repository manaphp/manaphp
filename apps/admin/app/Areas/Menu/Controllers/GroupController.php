<?php

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use ManaPHP\Mvc\Controller;

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

    public function createAction()
    {
        return Group::rCreate();
    }

    public function editAction()
    {
        return Group::rUpdate();
    }

    public function deleteAction()
    {
        return Group::rDelete();
    }
}