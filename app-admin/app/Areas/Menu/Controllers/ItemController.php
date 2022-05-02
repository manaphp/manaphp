<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Item;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class ItemController extends Controller
{
    public function indexAction()
    {
        return Item::search(['group_id'])
            ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
            ->all();
    }

    public function createAction(Item $item)
    {
        return $item->create();
    }

    public function editAction(Item $item)
    {
        return $item->update();
    }

    public function deleteAction(Item $item)
    {
        return $item->delete();
    }
}