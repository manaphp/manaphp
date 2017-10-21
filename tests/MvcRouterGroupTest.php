<?php
namespace Tests;

use ManaPHP\Mvc\Router\Group;
use PHPUnit\Framework\TestCase;

class MvcRouterGroupTest extends TestCase
{
    public function test_construct()
    {
        $group = new Group();

        $this->assertEquals(['controller' => 'index', 'action' => 'index', 'params' => []], $group->match('/'));
        $this->assertEquals(['controller' => 'a', 'action' => 'index', 'params' => []], $group->match('/a'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => []], $group->match('/a/b'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => ['c']], $group->match('/a/b/c'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => ['c', 'd', 'e']], $group->match('/a/b/c/d/e'));
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

        $group = new \ManaPHP\Mvc\Router\Group();
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

        foreach ($tests as $n => $test) {
            $parts = $group->match($test['uri']);
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

        $group = new Group();
        $group->add('/docs/index', 'documentation2::index');

        $group->addPost('/docs/index', 'documentation3::index');

        $group->addGet('/docs/index', 'documentation4::index');

        $group->addPut('/docs/index', 'documentation5::index');

        $group->addDelete('/docs/index', 'documentation6::index');

        $group->addOptions('/docs/index', 'documentation7::index');

        $group->addHead('/docs/index', 'documentation8::index');

        foreach ($tests as $n => $test) {
            $parts = $group->match($test['uri'], $test['method'] ?: 'GET');
            $this->assertEquals($test['controller'], $parts['controller'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['action'], $parts['action'], 'Testing ' . $test['uri']);
            $this->assertEquals($test['params'], $parts['params'], 'Testing ' . $test['uri']);
        }
    }

    public function test_add_usage()
    {
        $group = new Group();

        $group->add('/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/:params', 'posts::show');

        $parts = $group->match('/news/2016/03/12/china');
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
        $group = new Group();
        $group->add('/some/{name}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}', 'c::a');
        $group->add('/some/{name}/{id:[0-9]+}/{date}', 'c::a');

        foreach ($tests as $n => $test) {
            $parts = $group->match($test['uri'], $test['method'] ?: 'GET');
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
        $group = new Group();
        foreach ($routes as $route => $paths) {
            $parts = $group->match($route, 'GET');
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertNotFalse($parts);
            $this->assertEquals($paths['controller'], $parts['controller']);
            $this->assertEquals($paths['action'], $parts['action']);
        }
    }

    public function test_shortPaths_usage()
    {
        $group = new Group();
        $group->add('/', 'user::list');

        $parts = $group->match('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('list', $parts['action']);

        $group = new Group();
        $group->add('/', 'user::list');
        $parts = $group->match('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('list', $parts['action']);

        $group = new Group();
        $group->add('/', 'user');
        $parts = $group->match('/', 'GET');
        $this->assertNotFalse($parts);
        $this->assertEquals('user', $parts['controller']);
        $this->assertEquals('index', $parts['action']);
    }
}
