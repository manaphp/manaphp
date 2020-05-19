<?php
namespace App\Widgets;

use App\Areas\Menu\Models\Group;
use ManaPHP\Query;
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
        $groups = Group::select(['group_id', 'group_name', 'icon'])
            ->orderBy('display_order DESC, group_id ASC')
            ->with(['items' => static function (Query $query) {
                return $query
                    ->select(['item_id', 'item_name', 'url', 'icon', 'group_id'])
                    ->orderBy('display_order DESC, item_id ASC');
            }])
            ->all();

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

            $group['items'] = $items;
            $menu[] = $group;
        }

        return ['menu' => $menu];
    }
}