<?php

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use ManaPHP\Mvc\Controller;

class GroupController extends Controller
{
    public function indexAction()
    {
        return $this->request->isAjax()
            ? Group::search(['group_id'])
                ->orderBy('display_order DESC, group_id ASC')
                ->fetch(true)
            : null;
    }

    public function listAction()
    {
        return $this->request->isAjax() ? Group::all([], null, ['group_id', 'group_name']) : null;
    }

    public function createAction()
    {
        return Group::viewOrCreate();
    }

    public function editAction()
    {
        return Group::viewOrUpdate();
    }

    public function deleteAction()
    {
        return Group::viewOrDelete();
    }
}