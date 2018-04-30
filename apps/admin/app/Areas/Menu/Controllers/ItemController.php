<?php
namespace App\Admin\Areas\Menu\Controllers;

use App\Admin\Areas\Menu\Models\Item;
use ManaPHP\Mvc\Controller;

class ItemController extends Controller
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            return Item::criteria()
                ->whereRequest(['group_id'])
                ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
                ->execute();
        }
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            return Item::createOrFail();
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            return Item::updateOrFail();
        }
    }

    public function deleteAction()
    {
        if ($this->request->isPost()) {
            return $this->response->setJsonContent(Item::deleteOrFail());
        }
    }
}