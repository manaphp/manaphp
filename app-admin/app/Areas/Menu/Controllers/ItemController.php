<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Item;
use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\View;

#[Authorize('@index')]
#[RequestMapping('/menu/item')]
class ItemController extends Controller
{
    #[View]
    #[GetMapping('')]
    public function indexAction()
    {
        return Item::select()
            ->whereCriteria($this->request->all(), ['group_id'])
            ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
            ->all();
    }

    #[PostMapping]
    public function createAction()
    {
        return Item::fillCreate($this->request->all());
    }

    #[PostMapping]
    public function editAction(Item $item)
    {
        return $item->fillUpdate($this->request->all());
    }

    #[PostMapping]
    public function deleteAction(Item $item)
    {
        return $item->delete();
    }
}