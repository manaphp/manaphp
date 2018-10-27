<?php
namespace App\Widgets;

use App\Areas\Menu\Models\Group;
use App\Areas\Menu\Models\Item;
use App\Areas\Rbac\Models\Permission;
use ManaPHP\View\Widget;

class SideMenuWidget extends Widget
{
    public function run($vars = [])
    {
        $groups = Group::criteria()
            ->orderBy('display_order DESC, group_id ASC')
            ->fetch(true);

        $menu = [];
        foreach ($groups as $group) {
            $items = Item::criteria()
                ->where(['group_id' => $group['group_id']])
                ->orderBy('display_order DESC, item_id ASC')
                ->fetch(true);
            if (count($items) === 0) {
                continue;
            }
            
            $menu[] = [
                'group_id' => $group['group_id'],
                'group_name' => $group['group_name'],
                'items' => $items
            ];
        }

        return ['menu' => $menu];
    }
}