<?php
namespace App\Widgets;

use App\Areas\Menu\Models\Group;
use App\Areas\Menu\Models\Item;
use ManaPHP\View\Widget;

/**
 * Class SideMenuWidget
 * @package App\Widgets
 * @property-read \ManaPHP\AuthorizationInterface $authorization
 */
class SideMenuWidget extends Widget
{
    public function run($vars = [])
    {
        $groups = Group::query()
            ->orderBy('display_order DESC, group_id ASC')
            ->fetch(true);

        $menu = [];
        foreach ($groups as $group) {
            $items = Item::query()
                ->where(['group_id' => $group['group_id']])
                ->orderBy('display_order DESC, item_id ASC')
                ->fetch(true);

            foreach ($items as $k => $item) {
                if (!$this->authorization->isAllowed($item['url'])) {
                    unset($items[$k]);
                }
            }

            if (!$items) {
                continue;
            }

            $group['items'] = $items;
            $menu[] = $group;
        }

        return ['menu' => $menu];
    }
}