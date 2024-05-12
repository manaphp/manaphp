<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Item;
use App\Areas\Menu\Repositories\ItemRepository;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Restrictions;

#[Authorize('@index')]
#[RequestMapping('/menu/item')]
class ItemController extends Controller
{
    #[Autowired] protected ItemRepository $itemRepository;

    #[ViewGetMapping('')]
    public function indexAction()
    {
        return Item::select()
            ->where(Restrictions::of($this->request->all(), ['group_id']))
            ->orderBy(['group_id' => SORT_ASC, 'display_order' => SORT_DESC, 'item_id' => SORT_ASC])
            ->all();
    }

    #[PostMapping]
    public function createAction()
    {
        return $this->itemRepository->create($this->request->all());
    }

    #[PostMapping]
    public function editAction()
    {
        return $this->itemRepository->update($this->request->all());
    }

    #[PostMapping]
    public function deleteAction(int $item_id)
    {
        return $this->itemRepository->deleteById($item_id);
    }
}