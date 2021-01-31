<?php

namespace Tests;

use ManaPHP\Di\Container;
use ManaPHP\Mvc\Factory;
use ManaPHP\Http\Router;
use ManaPHP\Http\Router\Route;
use ManaPHP\Http\RouterContext;
use PHPUnit\Framework\TestCase;

class MyRouter extends Router
{
    public function setControllerName($controller)
    {
        $this->_context->controller = $controller;
    }

    public function setActionName($action)
    {
        $this->_context->action = $action;
    }
}

class MvcRouterTest extends TestCase
{
    public function setUp()
    {
        new Factory();
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

    public function test_getRewriteUri()
    {
        container('request')->getContext()->_REQUEST['_url'] = '/some/route';

        $router = new Router();

        //first try getting from url
        $this->assertEquals('/some/route', $router->getRewriteUri());

        unset(container('request')->getContext()->_REQUEST['_url']);
        $this->assertEquals('/', $router->getRewriteUri());
    }

    public function test_createUrl()
    {
        $router = new Router();

        $router->match('/article/list', 'GET');

        Container::getDefault()->getShared('alias')->set('@web', '');

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

        $this->assertInstanceOf(RouterContext::class, $router->match('/', 'GET'));
        $this->assertEquals('index', $router->getController());
        $this->assertEquals('index', $router->getAction());
        $this->assertEquals([], $router->getParams());

        $this->assertInstanceOf(RouterContext::class, $router->match('/a', 'GET'));
        $this->assertEquals('a', $router->getController());
        $this->assertEquals('index', $router->getAction());
        $this->assertEquals([], $router->getParams());

        $this->assertInstanceOf(RouterContext::class, $router->match('/a/b', 'GET'));
        $this->assertEquals('a', $router->getController());
        $this->assertEquals('b', $router->getAction());
        $this->assertEquals([], $router->getParams());

        $this->assertInstanceOf(RouterContext::class, $router->match('/a/b/c', 'GET'));
        $this->assertEquals('a', $router->getController());
        $this->assertEquals('b', $router->getAction());
        $this->assertEquals(['c'], $router->getParams());

        $this->assertInstanceOf(RouterContext::class, $router->match('/a/b/c/d/e', 'GET'));
        $this->assertEquals('a', $router->getController());
        $this->assertEquals('b', $router->getAction());
        $this->assertEquals(['c/d/e'], $router->getParams());
    }

    public function test_router()
    {
        $tests = array(
            array(
                'uri'        => '',
                'controller' => 'index',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/',
                'controller' => 'index',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/documentation/index/hello/ñda/dld/cc-ccc',
                'controller' => 'documentation',
                'action'     => 'index',
                'params'     => ['hello/ñda/dld/cc-ccc']
            ),
            array(
                'uri'        => '/documentation/index/',
                'controller' => 'documentation',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/documentation/index',
                'controller' => 'documentation',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/documentation/',
                'controller' => 'documentation',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/documentation',
                'controller' => 'documentation',
                'action'     => 'index',
            ),
            array(
                'uri'        => '/system/admin/a/edit/hello/adp',
                'controller' => 'admin',
                'action'     => 'edit',
                'params'     => ['hello/adp']
            ),
            array(
                'uri'        => '/es/news',
                'controller' => 'news',
                'action'     => 'index',
                'params'     => array('language' => 'es')
            ),
            array(
                'uri'        => '/admin/posts/edit/100',
                'controller' => 'posts',
                'action'     => 'edit',
                'params'     => array('id' => 100)
            ),
            array(
                'uri'        => '/posts/2010/02/10/title/content',
                'controller' => 'posts',
                'action'     => 'show',
                'params'     => array('year' => '2010', 'month' => '02', 'day' => '10', 0 => 'title/content')
            ),
            array(
                'uri'        => '/manual/en/translate.adapter.html',
                'controller' => 'manual',
                'action'     => 'show',
                'params'     => array('language' => 'en', 'file' => 'translate.adapter')
            ),
            array(
                'uri'        => '/named-manual/en/translate.adapter.html',
                'controller' => 'manual',
                'action'     => 'show',
                'params'     => array('language' => 'en', 'file' => 'translate.adapter')
            ),
            array(
                'uri'        => '/posts/1999/s/le-nice-title',
                'controller' => 'posts',
                'action'     => 'show',
                'params'     => array('year' => '1999', 'title' => 'le-nice-title')
            ),
            array(
                'uri'        => '/feed/fr/blog/ema.json',
                'controller' => 'feed',
                'action'     => 'get',
                'params'     => array('lang' => 'fr', 'blog' => 'ema', 'type' => 'json')
            ),
            array(
                'uri'        => '/posts/delete/150',
                'controller' => 'posts',
                'action'     => 'delete',
                'params'     => array('id' => '150')
            ),
            array(
                'uri'        => '/very/static/route',
                'controller' => 'static',
                'action'     => 'route',
            ),
        );

        $router = new Router();
        $router->add('/', 'index::index');

        $router->add('/system/{controller}/a/{action}/{params}');

        $router->add('/{language:[a-z]{2}}/{controller}');

        $router->add('/admin/{controller}/{action}/{id:int}');

        $router->add('/posts/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/{params}', 'posts::show');

        $router->add('/manual/{language:[a-z]{2}}/{file:[a-z\.]+}\.html', 'manual::show');

        $router->add('/named-manual/{language:([a-z]{2})}/{file:[a-z\.]+}\.html', 'manual::show');

        $router->add('/very/static/route', 'static::route');

        $router->add('/feed/{lang:[a-z]+}/blog/{blog:[a-z\-]+}\.{type:[a-z\-]+}', 'feed::get');

        $router->add('/posts/{year:[0-9]+}/s/{title:[a-z\-]+}', 'posts::show');

        $router->add('/posts/delete/{id}', 'posts::delete');

        $router->add('/show/{id:video([0-9]+)}/{title:[a-z\-]+}', 'videos::show');

        foreach ($tests as $n => $test) {
            $this->assertInstanceOf(RouterContext::class, $router->match($test['uri'], 'GET'));
            $this->assertEquals($test['controller'], $router->getController(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getAction(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_add()
    {
        $tests = array(
            array(
                'method'     => null,
                'uri'        => '/documentation/index/hello',
                'controller' => 'documentation',
                'action'     => 'index',
                'params'     => array('hello')
            ),
            array(
                'method'     => 'POST',
                'uri'        => '/docs/index',
                'controller' => 'documentation3',
                'action'     => 'index',
                'params'     => array()
            ),
            array(
                'method'     => 'GET',
                'uri'        => '/docs/index',
                'controller' => 'documentation4',
                'action'     => 'index',
                'params'     => array()
            ),
            array(
                'method'     => 'PUT',
                'uri'        => '/docs/index',
                'controller' => 'documentation5',
                'action'     => 'index',
                'params'     => array()
            ),
            array(
                'method'     => 'DELETE',
                'uri'        => '/docs/index',
                'controller' => 'documentation6',
                'action'     => 'index',
                'params'     => array()
            ),
            array(
                'method'     => 'HEAD',
                'uri'        => '/docs/index',
                'controller' => 'documentation8',
                'action'     => 'index',
                'params'     => array()
            ),
        );

        $router = new Router();
        $router->add('/docs/index', 'documentation2::index');

        $router->addPost('/docs/index', 'documentation3::index');

        $router->addGet('/docs/index', 'documentation4::index');

        $router->addPut('/docs/index', 'documentation5::index');

        $router->addDelete('/docs/index', 'documentation6::index');

        $router->addHead('/docs/index', 'documentation8::index');

        foreach ($tests as $n => $test) {
            $this->assertInstanceOf(RouterContext::class, $router->match($test['uri'], $test['method'] ?: 'GET'));
            $this->assertEquals($test['controller'], $router->getController(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getAction(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_add_usage()
    {
        $router = new Router();

        $router->add('/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/{params}', 'posts::show');

        $this->assertInstanceOf(RouterContext::class, $router->match('/news/2016/03/12/china', 'GET'));
        $this->assertEquals('posts', $router->getController());
        $this->assertEquals('show', $router->getAction());
        $this->assertEquals(['year' => '2016', 'month' => '03', 'day' => '12', 'china'], $router->getParams());
    }

    public function test_params()
    {
        $tests = array(
            array(
                'method'     => null,
                'uri'        => '/some/hattie',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => array('name' => 'hattie')
            ),
            array(
                'method'     => null,
                'uri'        => '/some/hattie/100',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => array('name' => 'hattie', 'id' => 100)
            ),
            array(
                'method'     => null,
                'uri'        => '/some/hattie/100/2011-01-02',
                'controller' => 'c',
                'action'     => 'a',
                'params'     => array('name' => 'hattie', 'id' => 100, 'date' => '2011-01-02')
            ),
        );
        $router = new Router();
        $router->add('/some/{name}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $router->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $this->assertInstanceOf(RouterContext::class, $router->match($test['uri'], $test['method'] ?: 'GET'));
            $this->assertEquals($test['controller'], $router->getController(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getAction(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_removeExtraSlashes()
    {
        $routes = array(
            '/index/'          => array(
                'controller' => 'index',
                'action'     => 'index',
            ),
            '/session/start/'  => array(
                'controller' => 'session',
                'action'     => 'start'
            ),
            '/users/edit/100/' => array(
                'controller' => 'users',
                'action'     => 'edit'
            ),
        );
        $router = new Router();
        foreach ($routes as $route => $paths) {
            $this->assertInstanceOf(RouterContext::class, $router->match($route, 'GET'));
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertEquals($paths['controller'], $router->getController());
            $this->assertEquals($paths['action'], $router->getAction());
        }
    }

    public function test_shortPaths_usage()
    {
        $router = new Router();
        $router->add('/', 'user::list');

        $this->assertInstanceOf(RouterContext::class, $router->match('/', 'GET'));
        $this->assertEquals('user', $router->getController());
        $this->assertEquals('list', $router->getAction());

        $router = new Router();
        $router->add('/', 'user::list');
        $this->assertInstanceOf(RouterContext::class, $router->match('/', 'GET'));
        $this->assertEquals('user', $router->getController());
        $this->assertEquals('list', $router->getAction());

        $router = new Router();
        $router->add('/', 'user');
        $this->assertInstanceOf(RouterContext::class, $router->match('/', 'GET'));

        $this->assertEquals('user', $router->getController());
        $this->assertEquals('index', $router->getAction());
    }
}