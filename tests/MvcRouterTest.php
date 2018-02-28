<?php
namespace Tests;

use ManaPHP\Di;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mvc\Router;
use ManaPHP\Mvc\Router\Route;
use PHPUnit\Framework\TestCase;

class MyRouter extends Router
{
    public function setControllerName($controller)
    {
        $this->_controller = $controller;
    }

    public function setActionName($action)
    {
        $this->_action = $action;
    }
}

class MvcRouterTest extends TestCase
{
    public function setUp()
    {
        new FactoryDefault();
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

    public function test_getRewriteUri()
    {
        $_GET['_url'] = '/some/route';
        $_SERVER['PATH_INFO'] = '/another/route';

        $router = new Router();

        //first try getting from url
        $this->assertEquals('/some/route', $router->getRewriteUri());

        //second try getting form request_uri
        unset($_GET['_url']);
        $this->assertEquals('/another/route', $router->getRewriteUri());

        //second try getting form request_uri
        unset($_GET['_url']);
        $_SERVER['PATH_INFO'] = '/another/route2';
        $this->assertEquals('/another/route2', $router->getRewriteUri());
    }

    public function test_createUrl()
    {
        $router = new Router();

        $router->handle('/article/list','GET');

        Di::getDefault()->alias->set('@web', '');

        $this->assertEquals('/article/list', $router->createUrl(''));
        $this->assertEquals('/article/create', $router->createUrl('create'));
        $this->assertEquals('/article', $router->createUrl('index'));
        $this->assertEquals('/news/detail', $router->createUrl('news/detail'));
        $this->assertEquals('/news', $router->createUrl('news/index'));
        $this->assertEquals('/news', $router->createUrl('news/'));
        $this->assertEquals('/', $router->createUrl('/'));

        $this->assertEquals('/blog', $router->createUrl('/blog'));
        $this->assertEquals('/blog', $router->createUrl('/blog/'));
        $this->assertEquals('/blog', $router->createUrl('/blog/index'));
        $this->assertEquals('/blog', $router->createUrl('/blog/index/index'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post/'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post/index'));
        $this->assertEquals('/blog/post/detail', $router->createUrl('/blog/post/detail'));

        $this->assertEquals('/article/list', $router->createUrl('list', []));
        $this->assertEquals('/article/list#hot', $router->createUrl(['list', '#' => 'hot']));
        $this->assertEquals('/article/list?q=hello', $router->createUrl(['list', 'q' => 'hello']));
        $this->assertEquals('/article/list?q=hello#hot', $router->createUrl(['list', 'q' => 'hello', '#' => 'hot']));
    }
}