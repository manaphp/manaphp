<?php

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use ManaPHP\Mvc\Controller;

class GroupController extends Controller
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            return Group::criteria()
                ->whereRequest(['group_id'])
                ->orderBy('display_order DESC, group_id ASC')
                ->execute();
        }
    }

    public function listAction()
    {
        if ($this->request->isAjax()) {
            return Group::lists();
        }
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            return Group::createOrFail();
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            return Group::updateOrFail();
        }
    }

    public function deleteAction()
    {
        if ($this->request->isPost()) {
            return $this->response->setJsonContent(Group::deleteOrFail());
        }
    }
}