<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/15
 * Time: 22:15
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MvcRouterRouteTest extends TestCase
{
    public function setUp()
    {
        new \ManaPHP\Di\FactoryDefault();
    }

    public function test_construct()
    {
        //  literal route test
        $route = new \ManaPHP\Mvc\Router\Route('/blog/edit');
        $this->assertEquals([], $route->match('/blog/edit'));

        // :module, :controller, :action, :params
        $route = new \ManaPHP\Mvc\Router\Route('/:controller/:action/:params');
        $this->assertEquals(['controller' => 'blog', 'action' => 'edit', 'params' => 'a/b/c'], $route->match('/blog/edit/a/b/c'));
        //  normal pcre
        $route = new \ManaPHP\Mvc\Router\Route('/blog/{user:[a-z0-9]{4,}}/view-{id:\d+}.html');
        $this->assertEquals(['user' => 'mana', 'id' => '1234'], $route->match('/blog/mana/view-1234.html'));

        $route = new \ManaPHP\Mvc\Router\Route('/blog/{id:\d+}');
        $this->assertEquals(['id' => '1234'], $route->match('/blog/1234'));
    }

    public function test_params()
    {
        $router = new ManaPHP\Mvc\Router();

        $tests = array(
            array(
                'uri' => '/some/hattie',
                'controller' => 'c',
                'action' => 'a',
                'name' => 'hattie'
            ),
            array(
                'uri' => '/some/hattie/100',
                'controller' => 'c',
                'action' => 'a',
                'name' => 'hattie',
                'id' => 100
            ),
            array(
                'uri' => '/some/hattie/100/2011-01-02',
                'controller' => 'c',
                'action' => 'a',
                'name' => 'hattie',
                'id' => 100,
                'date' => '2011-01-02'
            ),
        );
        $group = new \ManaPHP\Mvc\Router\Group();
        $group->add('/some/{name}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $this->assertEquals($test, array_merge(['uri' => $test['uri']], $group->match($test['uri'])));
        }
    }

    public function test_shortPaths()
    {
        $route = new \ManaPHP\Mvc\Router\Route('/', 'feed');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'feed'
        ));

        $route = new ManaPHP\Mvc\Router\Route('/', 'feed::get');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'feed',
            'action' => 'get',
        ));

        $route = new \ManaPHP\Mvc\Router\Route('/', 'posts::show');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'posts',
            'action' => 'show',
        ));

        $route = new \ManaPHP\Mvc\Router\Route('/', 'posts::show');
        $this->assertEquals($route->match('/'), array(
            'controller' => 'posts',
            'action' => 'show',
        ));
    }

    public function test_rest()
    {
        $route = new \ManaPHP\Mvc\Router\Route('/users', [], 'REST');
        $this->assertEquals(['action' => 'list'], $route->match('/users', 'GET'));
        $this->assertEquals(['action' => 'create'], $route->match('/users', 'POST'));
        $this->assertEquals(['action' => 'detail', 'params' => '123'], $route->match('/users/123', 'GET'));
        $this->assertEquals(['action' => 'update', 'params' => '123'], $route->match('/users/123', 'POST'));
        $this->assertEquals(['action' => 'update', 'params' => '123'], $route->match('/users/123', 'PUT'));
        $this->assertEquals(['action' => 'delete', 'params' => '123'], $route->match('/users/123', 'DELETE'));

        $route = new \ManaPHP\Mvc\Router\Route('/users/{user_id:int}/orders', [], 'REST');
        $this->assertEquals(['action' => 'list', 'user_id' => 1], $route->match('/users/1/orders', 'GET'));
        $this->assertEquals(['action' => 'create', 'user_id' => 1], $route->match('/users/1/orders', 'POST'));
        $this->assertEquals(['action' => 'detail', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'GET'));
        $this->assertEquals(['action' => 'update', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'POST'));
        $this->assertEquals(['action' => 'update', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'PUT'));
        $this->assertEquals(['action' => 'delete', 'user_id' => 1, 'params' => '123'], $route->match('/users/1/orders/123', 'DELETE'));
    }
}