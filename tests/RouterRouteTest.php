<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Router;
use ManaPHP\Router\Route;
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
        $this->assertEquals([], $route->match('/blog/edit'));

        // :module, :controller, :action, :params
        $route = new Route('/:controller/:action/:params');
        $this->assertEquals(['controller' => 'blog', 'action' => 'edit', 'params' => 'a/b/c'], $route->match('/blog/edit/a/b/c'));
        //  normal pcre
        $route = new Route('/blog/{user:[a-z0-9]{4,}}/view-{id:\d+}.html');
        $this->assertEquals(['user' => 'mana', 'id' => '1234'], $route->match('/blog/mana/view-1234.html'));

        $route = new Route('/blog/{id:\d+}');
        $this->assertEquals(['id' => '1234'], $route->match('/blog/1234'));
    }

    public function test_params()
    {
        $tests = array(
            array(
                'uri' => '/some/hattie',
                'controller' => 'c',
                'action' => 'a',
                'params' => ['name' => 'hattie']
            ),
            array(
                'uri' => '/some/hattie/100',
                'controller' => 'c',
                'action' => 'a',
                'params' => [
                    'name' => 'hattie',
                    'id' => 100
                ]
            ),
            array(
                'uri' => '/some/hattie/100/2011-01-02',
                'controller' => 'c',
                'action' => 'a',
                'params' => [
                    'name' => 'hattie',
                    'id' => 100,
                    'date' => '2011-01-02'
                ]
            ),
        );
        $router = new Router();
        $router->add('/some/{name}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $this->assertEquals($test, array_merge(['uri' => $test['uri']], $router->matchRoute($test['uri'])));
        }
    }

    public function test_shortPaths()
    {
        $route = new Route('/', 'feed');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'feed'
        ));

        $route = new Route('/', 'feed::get');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'feed',
            'action' => 'get',
        ));

        $route = new Route('/', 'posts::show');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'posts',
            'action' => 'show',
        ));

        $route = new Route('/', 'posts::show');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'posts',
            'action' => 'show',
        ));
    }

    public function test_rest()
    {
        $route = new Route('/users', [], 'REST');
        $this->assertEquals(['action' => 'list'], $route->match('/users', 'GET'));
        $this->assertEquals(['action' => 'create'], $route->match('/users', 'POST'));
        $this->assertEquals(['action' => 'detail', 'params' => '123'], $route->match('/users/123', 'GET'));
        $this->assertEquals(['action' => 'update', 'params' => '123'], $route->match('/users/123', 'POST'));
        $this->assertEquals(['action' => 'update', 'params' => '123'], $route->match('/users/123', 'PUT'));
        $this->assertEquals(['action' => 'delete', 'params' => '123'], $route->match('/users/123', 'DELETE'));

        $route = new Route('/users/{user_id:int}/orders', [], 'REST');
        $this->assertEquals(['action' => 'list', 'user_id' => 1], $route->match('/users/1/orders', 'GET'));
        $this->assertEquals(['action' => 'create', 'user_id' => 1], $route->match('/users/1/orders', 'POST'));
        $this->assertEquals(['action' => 'detail', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'GET'));
        $this->assertEquals(['action' => 'update', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'POST'));
        $this->assertEquals(['action' => 'update', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'PUT'));
        $this->assertEquals(['action' => 'delete', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'DELETE'));
    }


}