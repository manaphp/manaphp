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

    public function test_construct()
    {
        $router = new Router();

        $this->assertEquals(['controller' => 'index', 'action' => 'index', 'params' => []], $router->matchRoute('/'));
        $this->assertEquals(['controller' => 'a', 'action' => 'index', 'params' => []], $router->matchRoute('/a'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => []], $router->matchRoute('/a/b'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => ['c']], $router->matchRoute('/a/b/c'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => ['c', 'd', 'e']], $router->matchRoute('/a/b/c/d/e'));
    }

    public function test_router()
    {
        $tests = array(
            array(
                'uri' => '',
                'controller' => 'index',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/',
                'controller' => 'index',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/documentation/index/hello/ñda/dld/cc-ccc',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array('hello', 'ñda', 'dld', 'cc-ccc')
            ),
            array(
                'uri' => '/documentation/index/',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/documentation/index',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/documentation/',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/documentation',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'uri' => '/system/admin/a/edit/hello/adp',
                'controller' => 'admin',
                'action' => 'edit',
                'params' => array('hello', 'adp')
            ),
            array(
                'uri' => '/es/news',
                'controller' => 'news',
                'action' => 'index',
                'params' => array('language' => 'es')
            ),
            array(
                'uri' => '/admin/posts/edit/100',
                'controller' => 'posts',
                'action' => 'edit',
                'params' => array('id' => 100)
            ),
            array(
                'uri' => '/posts/2010/02/10/title/content',
                'controller' => 'posts',
                'action' => 'show',
                'params' => array('year' => '2010', 'month' => '02', 'day' => '10', 0 => 'title', 1 => 'content')
            ),
            array(
                'uri' => '/manual/en/translate.adapter.html',
                'controller' => 'manual',
                'action' => 'show',
                'params' => array('language' => 'en', 'file' => 'translate.adapter')
            ),
            array(
                'uri' => '/named-manual/en/translate.adapter.html',
                'controller' => 'manual',
                'action' => 'show',
                'params' => array('language' => 'en', 'file' => 'translate.adapter')
            ),
            array(
                'uri' => '/posts/1999/s/le-nice-title',
                'controller' => 'posts',
                'action' => 'show',
                'params' => array('year' => '1999', 'title' => 'le-nice-title')
            ),
            array(
                'uri' => '/feed/fr/blog/ema.json',
                'controller' => 'feed',
                'action' => 'get',
                'params' => array('lang' => 'fr', 'blog' => 'ema', 'type' => 'json')
            ),
            array(
                'uri' => '/posts/delete/150',
                'controller' => 'posts',
                'action' => 'delete',
                'params' => array('id' => '150')
            ),
            array(
                'uri' => '/very/static/route',
                'controller' => 'static',
                'action' => 'route',
                'params' => array()
            ),
        );

        $router = new Router();
        $router->add('/', 'index::index');

        $router->add('/system/:controller/a/:action/:params');

        $router->add('/{language:[a-z]{2}}/:controller');

        $router->add('/admin/:controller/:action/{id:\d+}');

        $router->add('/posts/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/:params', 'posts::show');

        $router->add('/manual/{language:[a-z]{2}}/{file:[a-z\.]+}\.html', 'manual::show');

        $router->add('/named-manual/{language:([a-z]{2})}/{file:[a-z\.]+}\.html', 'manual::show');

        $router->add('/very/static/route', 'static::route');

        $router->add('/feed/{lang:[a-z]+}/blog/{blog:[a-z\-]+}\.{type:[a-z\-]+}', 'feed::get');

        $router->add('/posts/{year:[0-9]+}/s/{title:[a-z\-]+}', 'posts::show');

        $router->add('/posts/delete/{id}', 'posts::delete');

        $router->add('/show/{id:video([0-9]+)}/{title:[a-z\-]+}', 'videos::show');

        foreach ($tests as $n => $test) {
            $parts = $router->matchRoute($test['uri']);
            $this->assertNotFalse($parts);
            $this->assertEquals($test['controller'], $parts['controller'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $parts['action'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $parts['params'], 'Testing ' . $test['uri']);
        }
    }

    public function test_add()
    {
        $tests = array(
            array(
                'method' => null,
                'uri' => '/documentation/index/hello',
                'controller' => 'documentation',
                'action' => 'index',
                'params' => array('hello')
            ),
            array(
                'method' => 'POST',
                'uri' => '/docs/index',
                'controller' => 'documentation3',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'method' => 'GET',
                'uri' => '/docs/index',
                'controller' => 'documentation4',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'method' => 'PUT',
                'uri' => '/docs/index',
                'controller' => 'documentation5',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'method' => 'DELETE',
                'uri' => '/docs/index',
                'controller' => 'documentation6',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'method' => 'OPTIONS',
                'uri' => '/docs/index',
                'controller' => 'documentation7',
                'action' => 'index',
                'params' => array()
            ),
            array(
                'method' => 'HEAD',
                'uri' => '/docs/index',
                'controller' => 'documentation8',
                'action' => 'index',
                'params' => array()
            ),
        );

        $router = new Router();
        $router->add('/docs/index', 'documentation2::index');

        $router->addPost('/docs/index', 'documentation3::index');

        $router->addGet('/docs/index', 'documentation4::index');

        $router->addPut('/docs/index', 'documentation5::index');

        $router->addDelete('/docs/index', 'documentation6::index');

        $router->addOptions('/docs/index', 'documentation7::index');

        $router->addHead('/docs/index', 'documentation8::index');

        foreach ($tests as $n => $test) {
            $parts = $router->matchRoute($test['uri'], $test['method'] ?: 'GET');
            $this->assertEquals($test['controller'], $parts['controller'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $parts['action'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $parts['params'], 'Testing ' . $test['uri']);
        }
    }

    public function test_add_usage()
    {
        $router = new Router();

        $router->add('/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/:params', 'posts::show');

        $parts = $router->matchRoute('/news/2016/03/12/china');
        $this->assertNotFalse($parts);
        $this->assertEquals('posts', $parts['controller']);
        $this->assertEquals('show', $parts['action']);
        $this->assertEquals('2016', $parts['params']['year']);
        $this->assertEquals('03', $parts['params']['month']);
        $this->assertEquals('12', $parts['params']['day']);
        $this->assertEquals('china', $parts['params']['0']);
    }

    public function test_params()
    {
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
        $router = new Router();
        $router->add('/some/{name}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $parts = $router->matchRoute($test['uri'], $test['method'] ?: 'GET');
            $this->assertEquals($test['controller'], $parts['controller'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $parts['action'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $parts['params'], 'Testing ' . $test['uri']);
        }
    }

    public function test_removeExtraSlashes()
    {
        $routes = array(
            '/index/' => array(
                'controller' => 'index',
                'action' => 'index',
            ),
            '/session/start/' => array(
                'controller' => 'session',
                'action' => 'start'
            ),
            '/users/edit/100/' => array(
                'controller' => 'users',
                'action' => 'edit'
            ),
        );
        $router = new Router();
        foreach ($routes as $route => $paths) {
            $parts = $router->matchRoute($route, 'GET');
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertNotFalse($parts);
            $this->assertEquals($paths['controller'], $parts['controller']);
            $this->assertEquals($paths['action'], $parts['action']);
        }
    }

    public function test_shortPaths_usage()
    {
        $router = new Router();
        $router->add('/', 'user::list');

        $parts = $router->matchRoute('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('list', $parts['action']);

        $router = new Router();
        $router->add('/', 'user::list');
        $parts = $router->matchRoute('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('list', $parts['action']);

        $router = new Router();
        $router->add('/', 'user');
        $parts = $router->matchRoute('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('index', $parts['action']);
    }
}