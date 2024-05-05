<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\Controller;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize('user')]
#[RequestMapping('/user/menu')]
class MenuController extends Controller
{
    #[ViewGetMapping]
    public function indexAction()
    {
        $menu = [
            ['group_name' => '个人中心',
             'items'      => [
                 ['item_name' => '最近登录', 'url' => '/user/login-log/latest'],
                 ['item_name' => '最近操作', 'url' => '/user/action-log/latest'],
                 ['item_name' => '修改密码', 'url' => '/user/password/change'],
             ]],
        ];

        foreach ($menu as &$group) {
            $group['icon'] ??= 'el-icon-caret-right';
            foreach ($group['items'] as &$item) {
                $item['icon'] ??= 'el-icon-caret-right';
            }
        }

        return $menu;
    }
}