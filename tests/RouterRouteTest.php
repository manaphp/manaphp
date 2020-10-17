<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Router;
use ManaPHP\Router\Route;
use ManaPHP\RouterContext;
use PHPUnit\Framework\TestCase;

class RouterRouteTest extends TestCase
{
    public function setUp()
    {
        new FactoryDefault();
    }

    public function test_construct()
    {
        //  literal route test
        $route = new Route('/blog/edit');
        $this->assertEquals(['controller' => 'index', 'action' => 'index'], $route->match('/blog/edit'));

        $route = new Route('/{controller}/{action}/{params}');
        $this->assertEquals(
            ['controller' => 'blog', 'action' => 'edit', 'params' => ['a/b/c']], $route->match('/blog/edit/a/b/c')
        );

        $route = new Route('/{area}/{controller}/{action}/{id}');
        $this->assertEquals(
            ['area' => 'api', 'controller' => 'blog', 'action' => 'view', 'params' => ['id' => '100']],
            $route->match('/api/blog/view/100')
        );

        //  normal pcre
        $route = new Route('/blog/{user:[a-z0-9]{4,}}/view-{id:\d+}.html');
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'index', 'params' => ['user' => 'mana', 'id' => '1234']],
            $route->match('/blog/mana/view-1234.html')
        );

        $route = new Route('/blog/{id:\d+}');
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'index', 'params' => ['id' => '1234']], $route->match('/blog/1234')
        );

        $route = new Route('/user/{controller}/{id}', ['action' => 'view']);
        $this->assertEquals(
            ['controller' => 'blog', 'action' => 'view', 'params' => ['id' => '1234']], $route->match('/user/blog/1234')
        );

        $route = new Route('/b/{action}/{id}', ['controller' => 'blog']);
        $this->assertEquals(
            ['controller' => 'blog', 'action' => 'view', 'params' => ['id' => '1234']], $route->match('/b/view/1234')
        );

        $route = new Route('/b/{id}', ['controller' => 'blog', 'action' => 'view']);
        $this->assertEquals(
            ['controller' => 'blog', 'action' => 'view', 'params' => ['id' => '1234']], $route->match('/b/1234')
        );

        $route = new Route('/blog', ['App\Controllers\BlogController']);
        $this->assertEquals(['controller' => 'Blog', 'action' => 'index'], $route->match('/blog'));

        $route = new Route('/blog', ['App\Controllers\BlogController']);
        $this->assertEquals(['controller' => 'Blog', 'action' => 'index'], $route->match('/blog'));

        $route = new Route('/blog', ['App\Controllers\Admin\BlogController']);
        $this->assertEquals(['controller' => 'Blog', 'action' => 'index', 'area' => 'Admin'], $route->match('/blog'));

        $route = new Route('/blog', ['App\Areas\Admin\Controllers\BlogController']);
        $this->assertEquals(['controller' => 'Blog', 'action' => 'index', 'area' => 'Admin'], $route->match('/blog'));
    }

    public function test_params()
    {
        $tests = array(
            array(
                'uri'        => '/some/hattie',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => ['name' => 'hattie']
            ),
            array(
                'uri'        => '/some/hattie/100',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => [
                    'name' => 'hattie',
                    'id'   => 100
                ]
            ),
            array(
                'uri'        => '/some/hattie/100/2011-01-02',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => [
                    'name' => 'hattie',
                    'id'   => 100,
                    'date' => '2011-01-02'
                ]
            ),
        );
        $router = new Router();
        $router->add('/some/{name}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $this->assertInstanceOf(RouterContext::class, $router->match($test['uri'], 'GET'));
            $this->assertEquals($test['controller'], $router->getController());
            $this->assertEquals($test['action'], $router->getAction());
            $this->assertEquals($test['params'], $router->getParams());
        }
    }

    public function test_shortPaths()
    {
        $route = new Route('/', 'feed');
        $this->assertEquals(['controller' => 'feed', 'action' => 'index'], $route->match('/'));

        $route = new Route('/', 'feed::get');
        $this->assertEquals(['controller' => 'feed', 'action' => 'get'], $route->match('/'));

        $route = new Route('/', 'posts::show');
        $this->assertEquals(['controller' => 'posts', 'action' => 'show'], $route->match('/'));

        $route = new Route('/', 'posts::show');
        $this->assertEquals(['controller' => 'posts', 'action' => 'show'], $route->match('/'));
    }

    public function test_rest()
    {
        $route = new Route('/users', [], 'REST');
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'index', 'params' => []], $route->match('/users', 'GET')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'create', 'params' => []], $route->match('/users', 'POST')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'detail', 'params' => ['123']], $route->match('/users/123', 'GET')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'update', 'params' => ['123']], $route->match('/users/123', 'POST')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'update', 'params' => ['123']], $route->match('/users/123', 'PUT')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'delete', 'params' => ['123']], $route->match('/users/123', 'DELETE')
        );

        $route = new Route('/users/{user_id:int}/orders', [], 'REST');
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'index', 'params' => ['user_id' => 1]],
            $route->match('/users/1/orders', 'GET')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'create', 'params' => ['user_id' => 1]],
            $route->match('/users/1/orders', 'POST')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'detail', 'params' => ['user_id' => 1, '123']],
            $route->match('/users/1/orders/123', 'GET')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'update', 'params' => ['user_id' => 1, '123']],
            $route->match('/users/1/orders/123', 'POST')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'update', 'params' => ['user_id' => 1, '123']],
            $route->match('/users/1/orders/123', 'PUT')
        );
        $this->assertEquals(
            ['controller' => 'index', 'action' => 'delete', 'params' => ['user_id' => 1, '123']],
            $route->match('/users/1/orders/123', 'DELETE')
        );
    }


}