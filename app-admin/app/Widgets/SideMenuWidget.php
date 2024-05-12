<?php
declare(strict_types=1);

namespace App\Widgets;

use App\Areas\Menu\Repositories\GroupRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Persistence\AdditionalRelationCriteria;

class SideMenuWidget extends Widget
{
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected GroupRepository $groupRepository;

    public function run($vars = [])
    {
        $fields = ['group_id', 'group_name', 'icon',
                   'items' => AdditionalRelationCriteria::of(
                       ['item_id', 'item_name', 'url', 'icon', 'group_id'],
                       ['display_order' => SORT_DESC, 'item_id' => SORT_ASC]
                   )];
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

            $group['items'] = $items;
            $menu[] = $group;
        }

        return ['menu' => $menu];
    }
}