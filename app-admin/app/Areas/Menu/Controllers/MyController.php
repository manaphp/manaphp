<?php
declare(strict_types=1);

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Repositories\GroupRepository;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[Authorize(Authorize::USER)]
#[RequestMapping('/menu/my')]
class MyController extends Controller
{
    #[Autowired] protected GroupRepository $groupRepository;

    #[GetMapping('')]
    public function indexAction()
    {
        $fields = ['group_id', 'group_name', 'icon',
                   'items' => ['item_id', 'item_name', 'url', 'icon', 'group_id']
        ];
        $orders = ['display_order' => SORT_DESC, 'group_id' => SORT_ASC];

        $groups = $this->groupRepository->all([], $fields, $orders);

        $menu = [];
        foreach ($groups as $group) {
            $items = $group['items'];
            foreach ($items as $k => $item) {
                $url = $item['url'];

                if (!$url || $url[0] !== '/') {
                    continue;
                }

                if (($pos = strpos($url, '?')) !== false) {
                    $url = substr($url, 0, $pos);
                }

                if (!$this->authorization->isAllowed($url)) {
                    unset($items[$k]);
                }
            }

            if (!$items) {
                continue;
            }

            $group['items'] = array_values($items);
            $menu[] = $group;
        }

        return $menu;
    }
}