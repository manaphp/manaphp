<?php

namespace Tests;

use ManaPHP\Authorization;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_inferControllerAction()
    {
        $di = new Factory();
        $authorization = new Authorization();
        $this->assertEquals(
            ['@ns.app\\Controllers\\IndexController', 'index'], $authorization->inferControllerAction('/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'index'], $authorization->inferControllerAction('/user/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'index'], $authorization->inferControllerAction('/user')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserInfoController', 'index'], $authorization->inferControllerAction('/user_info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('/user/info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('/user/Info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'latestOrders'],
            $authorization->inferControllerAction('/user/latest_orders')
        );

        $this->assertEquals(
            ['@ns.app\\Controllers\\UserInfoController', 'index'], $authorization->inferControllerAction('user_info/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('user/info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('user/Info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'latestOrders'],
            $authorization->inferControllerAction('user/latest_orders')
        );
    }

    public function test_inferControllerAction2()
    {
        $di = new Factory();
        $di->router->setAreas(['Blog']);

        $authorization = new Authorization();
        $this->assertEquals(
            ['@ns.app\\Controllers\\IndexController', 'index'], $authorization->inferControllerAction('/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'index'], $authorization->inferControllerAction('/user/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'index'], $authorization->inferControllerAction('/user')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserInfoController', 'index'], $authorization->inferControllerAction('/user_info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('/user/info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('/user/Info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'latestOrders'],
            $authorization->inferControllerAction('/user/latest_orders')
        );

        $this->assertEquals(
            ['@ns.app\\Controllers\\UserInfoController', 'index'], $authorization->inferControllerAction('user_info/')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('user/info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'info'], $authorization->inferControllerAction('user/Info')
        );
        $this->assertEquals(
            ['@ns.app\\Controllers\\UserController', 'latestOrders'],
            $authorization->inferControllerAction('user/latest_orders')
        );
    }

    public function test_inferControllerAction3()
    {
        $di = new Factory();
        $di->router->setAreas(['Blog']);

        $authorization = new Authorization();
        $this->assertEquals(
            ['@ns.app\\Controllers\\IndexController', 'index'], $authorization->inferControllerAction('/')
        );
        $this->assertEquals(
            ['@ns.app\\Areas\\Blog\\Controllers\\IndexController', 'index'],
            $authorization->inferControllerAction('/blog')
        );
        $this->assertEquals(
            ['@ns.app\\Areas\\Blog\\Controllers\\IndexController', 'index'],
            $authorization->inferControllerAction('/blog/')
        );
        $this->assertEquals(
            ['@ns.app\\Areas\\Blog\\Controllers\\UserController', 'index'],
            $authorization->inferControllerAction('/blog/user')
        );
        $this->assertEquals(
            ['@ns.app\\Areas\\Blog\\Controllers\\UserController', 'index'],
            $authorization->inferControllerAction('/blog/user/')
        );
        $this->assertEquals(
            ['@ns.app\\Areas\\Blog\\Controllers\\UserController', 'info'],
            $authorization->inferControllerAction('/blog/user/info')
        );
    }
}