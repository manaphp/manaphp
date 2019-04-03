<?php
namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Item;
use ManaPHP\Mvc\Controller;

class ItemController extends Controller
{
    public function indexAction()
    {
        return $this->request->isAjax()
            ? Item::query()
                ->whereSearch(['group_id'])
                ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
                ->fetch(true)
            : null;
    }

    public function createAction()
    {
        return Item::createOrNull();
    }

    public function editAction()
    {
        return Item::updateOrNull();
    }

    public function deleteAction()
    {
        return Item::deleteOrNull();
    }
}