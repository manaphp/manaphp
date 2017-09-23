<?php
namespace Tests;

use ManaPHP\Di;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Request;
use ManaPHP\Mvc\Router;
use ManaPHP\Mvc\Router\Route;
use PHPUnit\Framework\TestCase;
use Test\Path\RouteGroup;

require __DIR__ . '/Router/Group.php';

class MyRouter extends Router
{
    public function setModules($modules)
    {
        $this->_modules = $modules;
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

    public function test_router()
    {
        $_GET['_url'] = '';

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
        $group = new \Test\App\RouteGroup();
        $group->add('/', 'index::index');

        $group->add('/system/:controller/a/:action/:params');

        $group->add('/{language:[a-z]{2}}/:controller');

        $group->add('/admin/:controller/:action/{id:\d+}');

        $group->add('/posts/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/:params', 'posts::show');

        $group->add('/manual/{language:[a-z]{2}}/{file:[a-z\.]+}\.html', 'manual::show');

        $group->add('/named-manual/{language:([a-z]{2})}/{file:[a-z\.]+}\.html', 'manual::show');

        $group->add('/very/static/route', 'static::route');

        $group->add('/feed/{lang:[a-z]+}/blog/{blog:[a-z\-]+}\.{type:[a-z\-]+}', 'feed::get');

        $group->add('/posts/{year:[0-9]+}/s/{title:[a-z\-]+}', 'posts::show');

        $group->add('/posts/delete/{id}', 'posts::delete');

        $group->add('/show/{id:video([0-9]+)}/{title:[a-z\-]+}', 'videos::show');

        $router->mount($group, '/');
        foreach ($tests as $n => $test) {
            $router->handle($test['uri'], 'GET');
            $this->assertEquals('App', $router->getModuleName());
            $this->assertEquals($test['controller'], $router->getControllerName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getActionName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
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

        $di = new Di();

        $di->set('request', function () {
            return new Request();
        });
        $di->set('eventsManager', new \ManaPHP\Event\Manager());
        $router = new Router();
        $router->setDependencyInjector($di);
        $group = new \Test\App\RouteGroup();
        $group->add('/docs/index', 'documentation2::index');

        $group->addPost('/docs/index', 'documentation3::index');

        $group->addGet('/docs/index', 'documentation4::index');

        $group->addPut('/docs/index', 'documentation5::index');

        $group->addDelete('/docs/index', 'documentation6::index');

        $group->addOptions('/docs/index', 'documentation7::index');

        $group->addHead('/docs/index', 'documentation8::index');

        $router->mount($group, '/');
        foreach ($tests as $n => $test) {
            $router->handle($test['uri'], $test['method'] ?: 'GET');
            $this->assertEquals('App', $router->getModuleName());
            $this->assertEquals($test['controller'], $router->getControllerName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getActionName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_add_usage()
    {
        $group = new \Test\App\RouteGroup();

        $group->add('/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/:params', 'posts::show');

        $router = new Router();
        $router->mount($group, '/');
        $router->handle('/news/2016/03/12/china', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('App', $router->getModuleName());
        $this->assertEquals('posts', $router->getControllerName());
        $this->assertEquals('show', $router->getActionName());
        $this->assertEquals('2016', $router->getParams()['year']);
        $this->assertEquals('03', $router->getParams()['month']);
        $this->assertEquals('12', $router->getParams()['day']);
        $this->assertEquals('china', $router->getParams()['0']);
    }

    public function test_params()
    {
        $router = new Router();

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
        $group = new \Test\App\RouteGroup();
        $group->add('/some/{name}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        $router->mount($group, '/');

        foreach ($tests as $n => $test) {
            $router->handle($test['uri'], $test['method'] ?: 'GET');
            $this->assertEquals('App', $router->getModuleName());
            $this->assertEquals($test['controller'], $router->getControllerName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $router->getActionName(), 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $router->getParams(), 'Testing ' . $test['uri']);
        }
    }

    public function test_removeExtraSlashes()
    {
        $router = new Router();

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

        $router->mount(new \Test\App\RouteGroup(), '/');
        foreach ($routes as $route => $paths) {
            $router->handle($route, 'GET');
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertTrue($router->wasMatched());
            $this->assertEquals($paths['controller'], $router->getControllerName());
            $this->assertEquals($paths['action'], $router->getActionName());
        }
    }

    public function test_mount()
    {
        $router = new Router();

        $group = new \Test\Blog\RouteGroup();

        $group->add('/save', array(
            'action' => 'save'
        ));

        $group->add('/edit/{id}', array(
            'action' => 'edit'
        ));

        $group->add('/about', 'about::index');

        $router->mount($group, '/blog');

        $routes = array(
            '/blog/save' => array(
                'module' => 'Blog',
                'controller' => 'index',
                'action' => 'save',
            ),
            '/blog/edit/1' => array(
                'module' => 'Blog',
                'controller' => 'index',
                'action' => 'edit'
            ),
            '/blog/about' => array(
                'module' => 'Blog',
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
        $group = new \Test\Blog\RouteGroup();
        $group->add('/article/{id:\\d+}', 'article::detail');

        //single module usage
        $router = (new Router())->mount($group, '/');
        $router->handle('/article/1', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with binding to /blog path
        $router = (new Router())->mount($group, '/blog');
        $router->handle('/blog/article/1', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with binding to domain

        $_SERVER['HTTP_HOST'] = 'blog.manaphp.com';
        $router = (new Router())->mount($group, 'blog.manaphp.com');
        $router->handle('/article/1', 'GET', 'blog.manaphp.com');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        //multiple module usage with bind to domain
        $router = (new Router())->mount($group, 'blog.manaphp.com/p1/p2');
        $router->handle('/p1/p2/article/1', 'GET', 'blog.manaphp.com');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('Blog', $router->getModuleName());
        $this->assertEquals('article', $router->getControllerName());
        $this->assertEquals('detail', $router->getActionName());

        $groupPath = new RouteGroup();
        $groupDomain = new \Test\Domain\RouteGroup();
        $groupDomainPath = new \Test\DomainPath\RouteGroup();

        $router = new Router();
        $router->mount($groupPath, '/');
        $router->mount($groupDomain, 'blog.manaphp.com');
        $router->mount($groupDomainPath, 'www.manaphp.com/blog');
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

    public function test_shortPaths_usage()
    {
        $group = new \Test\App\RouteGroup();
        $group->add('/', 'user::list');
        $router = new Router();
        $router->mount($group, '/');

        $router->handle('/', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('user', $router->getControllerName());
        $this->assertEquals('list', $router->getActionName());

        $group = new \Test\App\RouteGroup();
        $group->add('/', 'user::list');
        $router = new Router();
        $router->mount($group, '/');
        $router->handle('/', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('App', $router->getModuleName());
        $this->assertEquals('user', $router->getControllerName());
        $this->assertEquals('list', $router->getActionName());

        $group = new \Test\App\RouteGroup();
        $group->add('/', 'user');
        $router = new Router();
        $router->mount($group, '/');
        $router->handle('/', 'GET');
        $this->assertTrue($router->wasMatched());
        $this->assertEquals('App', $router->getModuleName());
        $this->assertEquals('user', $router->getControllerName());
        $this->assertEquals('index', $router->getActionName());
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
        $router->setModules(['Home' => '/', 'Blog' => '/blog']);

        $router->setModuleName('Home');
        $router->setControllerName('Article');
        $router->setActionName('list');

        $this->assertEquals('/article/list', $router->createActionUrl(''));
        $this->assertEquals('/article/create', $router->createActionUrl('create'));
        $this->assertEquals('/article', $router->createActionUrl('index'));
        $this->assertEquals('/news/detail', $router->createActionUrl('news/detail'));
        $this->assertEquals('/news', $router->createActionUrl('news/index'));
        $this->assertEquals('/news', $router->createActionUrl('news/'));
        $this->assertEquals('/', $router->createActionUrl('/'));
        $this->assertEquals('/', $router->createActionUrl('index/index'));

        $this->assertEquals('/blog', $router->createActionUrl('/blog'));
        $this->assertEquals('/blog', $router->createActionUrl('/blog/'));
        $this->assertEquals('/blog', $router->createActionUrl('/blog/index'));
        $this->assertEquals('/blog', $router->createActionUrl('/blog/index/index'));
        $this->assertEquals('/blog/post', $router->createActionUrl('/blog/post'));
        $this->assertEquals('/blog/post', $router->createActionUrl('/blog/post/'));
        $this->assertEquals('/blog/post', $router->createActionUrl('/blog/post/index'));
        $this->assertEquals('/blog/post/detail', $router->createActionUrl('/blog/post/detail'));

        $this->assertEquals('/article/list', $router->createActionUrl('list', []));
        $this->assertEquals('/article/list#hot', $router->createActionUrl('list', ['#' => 'hot']));
        $this->assertEquals('/article/list?q=hello', $router->createActionUrl('list', ['q' => 'hello']));
        $this->assertEquals('/article/list?q=hello#hot', $router->createActionUrl('list', ['q' => 'hello', '#' => 'hot']));
    }
}