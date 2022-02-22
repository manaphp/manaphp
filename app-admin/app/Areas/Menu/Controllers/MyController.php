<?php

namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use App\Controllers\Controller;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('user')]
class MyController extends Controller
{
    public function indexAction()
    {
        $groups = Group::select(['group_id', 'group_name', 'icon'])
            ->orderBy(['display_order' => SORT_DESC, 'group_id' => SORT_ASC])
            ->with(
                [
                    'items' => static function (QueryInterface $query) {
                        return $query
                            ->select(['item_id', 'item_name', 'url', 'icon', 'group_id'])
                            ->orderBy('display_order DESC, item_id ASC');
                    }
                ]
            )
            ->all();

        $roles = $this->identity->getRoles();
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

                if (!$this->authorization->isAllowed($url, $roles)) {
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