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
    public function test_construct()
    {

        //  literal route test
        $route = new \ManaPHP\Mvc\Router\Route('/blog/edit');
        $this->assertTrue($route->isMatched('/blog/edit', $matches));
        $this->assertEquals([], $matches);

        // :module, :controller, :action, :params
        $route = new \ManaPHP\Mvc\Router\Route('/:module/:controller/:action/:params');
        $this->assertTrue($route->isMatched('/admin/blog/edit/a/b/c', $matches));
        $this->assertEquals('admin', $matches['module']);
        $this->assertEquals('blog', $matches['controller']);
        $this->assertEquals('edit', $matches['action']);
        $this->assertEquals('a/b/c', $matches['params']);

        //  normal pcre
        $route = new \ManaPHP\Mvc\Router\Route('/blog/{user:[a-z0-9]{4,}}/view-{id:\d+}.html');
        $this->assertTrue($route->isMatched('/blog/mana/view-1234.html', $matches));
        $this->assertEquals('1234', $matches['id']);
        $this->assertEquals('mana', $matches['user']);

        $route = new \ManaPHP\Mvc\Router\Route('/blog/{id:\d+}');
        $this->assertTrue($route->isMatched('/blog/1234', $matches));
        $this->assertEquals('1234', $matches['id']);

    }

    public function test_params()
    {
        $router = new ManaPHP\Mvc\Router(false);

        $tests = array(
            array(
                'method' => null,
                'uri' => '/some/hattie',
                'controller' => 'c',
                'action' => 'a',
                'params' => array('name' => 'hattie')
            ),
            array(
                'method' => null,
                'uri' => '/some/hattie/100',
                'controller' => 'c',
                'action' => 'a',
                'params' => array('name' => 'hattie', 'id' => 100)
            ),
            array(
                'method' => null,
                'uri' => '/some/hattie/100/2011-01-02',
                'controller' => 'c',
                'action' => 'a',
                'params' => array('name' => 'hattie', 'id' => 100, 'date' => '2011-01-02')
            ),
        );
        $group = new \ManaPHP\Mvc\Router\Group();
        $group->add('/some/{name}', ['controller' => 'c', 'action' => 'a']);
        $group->add('/some/{name}/{id:[0-9]+}', ['controller' => 'c', 'action' => 'a']);
        $group->add('/some/{name}/{id:[0-9]+}/{date}', ['controller' => 'c', 'action' => 'a']);

        $router->mount($group, 'app', '/');

        foreach ($tests as $n => $test) {
            $_SERVER['REQUEST_METHOD'] = $test['method'];

            $router->handle($test['uri']);
            $this->assertEquals(strtolower($test['controller']), strtolower($router->getControllerName()),
                'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getActionName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_shortPaths()
    {
        $route = new \ManaPHP\Mvc\Router\Route('/route0', 'feed');
        $this->assertEquals($route->getPaths(), array(
            'controller' => 'feed'
        ));

        $route = new ManaPHP\Mvc\Router\Route('/route1', 'feed::get');
        $this->assertEquals($route->getPaths(), array(
            'controller' => 'feed',
            'action' => 'get',
        ));

        $route = new \ManaPHP\Mvc\Router\Route('/route2', 'news::posts::show');
        $this->assertEquals($route->getPaths(), array(
            'module' => 'news',
            'controller' => 'posts',
            'action' => 'show',
        ));

        $route = new \ManaPHP\Mvc\Router\Route('/route3', 'posts::show');
        $this->assertEquals($route->getPaths(), array(
            'controller' => 'posts',
            'action' => 'show',
        ));
    }
}