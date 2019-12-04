<?php
namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Item;
use ManaPHP\Mvc\Controller;

class ItemController extends Controller
{
    public function indexAction()
    {
        return $this->request->isAjax()
            ? Item::search(['group_id'])
                ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
                ->fetch(true)
            : null;
    }

    public function createAction()
    {
        return Item::rCreate();
    }

    public function editAction()
    {
        return Item::rUpdate();
    }

    public function deleteAction()
    {
        return Item::rDelete();
    }
}