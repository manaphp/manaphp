<?php
namespace App\Admin\Widgets;

use App\Admin\Menu\Models\Group;
use App\Admin\Menu\Models\Item;
use App\Admin\Rbac\Models\Permission;
use ManaPHP\Mvc\Widget;
use ManaPHP\Utility\Text;

class SideMenuWidget extends Widget
{
    public function run($vars = [])
    {
        $groups = Group::criteria()
            ->orderBy('display_order DESC, group_id ASC')
            ->execute();

        $menu = [];
        foreach ($groups as $group) {
            $items = Item::criteria()
                ->where(['group_id' => $group['group_id']])
                ->orderBy('display_order DESC, item_id ASC')
                ->execute();
            if (count($items) === 0) {
                continue;
            }

            foreach ($items as $k => $item) {
                $permission = Permission::findById($item['permission_id']);
                $items[$k]['action'] = '/' . Text::underscore($permission->module_name) . '/' . Text::underscore($permission->controller_name) . '/' . $permission->action_name;
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