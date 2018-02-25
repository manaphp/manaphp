<?php
namespace Tests;

use ManaPHP\Di;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mvc\Router;
use ManaPHP\Mvc\Router\Route;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/Router/Group.php';

class MyRouter extends Router
{
    public function setGroups($groups)
    {
        $this->_groups = $groups;
    }

    public function setModuleName($module)
    {
        $this->_module = $module;
    }

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

    public function test_mount()
    {
        $router = new Router();
        $router->setDependencyInjector(new FactoryDefault());
        $router->getDependencyInjector()->alias->set('@ns.app', 'Test');

        $router->mount(['Blog3' => '/blog']);

        $routes = array(
            '/blog/save' => array(
                'module' => 'Blog3',
                'controller' => 'index',
                'action' => 'save',
            ),
            '/blog/edit/1' => array(
                'module' => 'Blog3',
                'controller' => 'index',
                'action' => 'edit'
            ),
            '/blog/about' => array(
                'module' => 'Blog3',
                'controller' => 'about',
                'action' => 'index'
            ),
        );

        foreach ($routes as $route => $paths) {
            $router->handle($route, 'GET');
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertTrue($router->wasMatched());
            $this->assertEquals($paths['module'], $router->getModuleName(), $route);
            $this->assertEquals($paths['controller'], $router->getControllerName(), $route);
            $this->assertEquals($paths['action'], $router->getActionName(), $route);
        }
    }

    public function test_mount_for_usage()
    {
        //single module usage
        $router = new Router();
        $router->setDependencyInjector(new FactoryDefault());
        $router->getDependencyInjector()->alias->set('@ns.app', 'Test');
        $router->mount(['Blog2' => '/']);

        $router->handle('/article/1', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog2', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with binding to /blog path
        $router->mount(['Blog2' => '/blog']);
        $router->handle('/blog/article/1', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog2', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with binding to domain

        $_SERVER['HTTP_HOST'] = 'blog.manaphp.com';
        $router->mount(['Blog2' => 'blog.manaphp.com']);
        $router->handle('/article/1', 'GET', 'blog.manaphp.com');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog2', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with bind to domain

        $router->mount(['Blog2' => 'blog.manaphp.com/p1/p2']);
        $router->handle('/p1/p2/article/1', 'GET', 'blog.manaphp.com');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog2', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        $router->mount(['Path' => '/', 'Domain' => 'blog.manaphp.com', 'DomainPath' => 'www.manaphp.com/blog']);

        $_SERVER['HTTP_HOST'] = 'blog.manaphp.com';
        $this->assertTrue($router->handle('/article', 'GET', 'blog.manaphp.com'));
        $this->assertEquals('Domain', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('index', $router->getActionName());

        $_SERVER['HTTP_HOST'] = 'manaphp.com';
        $this->assertTrue($router->handle('/blog/add', 'GET', 'manaphp.com'));
        $this->assertEquals('Path', $router->getModuleName());
        $this->assertEquals('blog', $router->getControllerName());
        $this->assertEquals('add', $router->getActionName());

        $_SERVER['HTTP_HOST'] = 'www.manaphp.com';
        $this->assertTrue($router->handle('/blog/comments/list', 'GET', 'www.manaphp.com'));
        $this->assertEquals('DomainPath', $router->getModuleName());
        $this->assertEquals('comments', $router->getControllerName());
        $this->assertEquals('list', $router->getActionName());
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

    public function test_createActionUrl()
    {
        $router = new MyRouter();
        $router->setGroups(['Home' => '/', 'Blog' => '/blog']);

        $router->setModuleName('Home');
        $router->setControllerName('Article');
        $router->setActionName('list');

        Di::getDefault()->alias->set('@web', '');

        $this->assertEquals('/article/list', $router->createUrl(''));
        $this->assertEquals('/article/create', $router->createUrl('create'));
        $this->assertEquals('/article', $router->createUrl('index'));
        $this->assertEquals('/news/detail', $router->createUrl('news/detail'));
        $this->assertEquals('/news', $router->createUrl('news/index'));
        $this->assertEquals('/news', $router->createUrl('news/'));
        $this->assertEquals('/', $router->createUrl('/'));
        $this->assertEquals('/', $router->createUrl('index/index'));

        $this->assertEquals('/blog', $router->createUrl('/blog'));
        $this->assertEquals('/blog', $router->createUrl('/blog/'));
        $this->assertEquals('/blog', $router->createUrl('/blog/index'));
        $this->assertEquals('/blog', $router->createUrl('/blog/index/index'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post/'));
        $this->assertEquals('/blog/post', $router->createUrl('/blog/post/index'));
        $this->assertEquals('/blog/post/detail', $router->createUrl('/blog/post/detail'));

        $this->assertEquals('/article/list', $router->createUrl('list', []));
        $this->assertEquals('/article/list#hot', $router->createUrl('list', ['#' => 'hot']));
        $this->assertEquals('/article/list?q=hello', $router->createUrl('list', ['q' => 'hello']));
        $this->assertEquals('/article/list?q=hello#hot', $router->createUrl('list', ['q' => 'hello', '#' => 'hot']));
    }
}