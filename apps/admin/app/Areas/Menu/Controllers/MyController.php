<?php
namespace App\Areas\Menu\Controllers;

use App\Areas\Menu\Models\Group;
use ManaPHP\Mvc\Controller;
use ManaPHP\Query;

class MyController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user'];
    }

    public function indexAction()
    {
        $groups = Group::select(['group_id', 'group_name', 'icon'])
            ->orderBy(['display_order' => SORT_DESC, 'group_id' => SORT_ASC])
            ->with(['items' =>static function (Query $query) {
                return $query
                    ->select(['item_id', 'item_name', 'url', 'icon', 'group_id'])
                    ->orderBy('display_order DESC, item_id ASC');
            }])
            ->fetch(true);

        $role = $this->identity->getRole();
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

                if (!$this->authorization->isAllowed($url, $role)) {
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