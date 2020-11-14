<?php

namespace Tests;

use ManaPHP\Mvc\Factory;
use ManaPHP\Http\Url;
use PHPUnit\Framework\TestCase;

class HttpUrlTest extends TestCase
{
    /**
     * @var \ManaPHP\Di
     */
    protected $_di;

    public function setup()
    {
        $this->_di = new Factory();
    }

    public function test_get()
    {
        $this->_di->alias->set('@web', '');
        $url = new Url();

        $this->assertEquals('/', $url->get('/'));
        $this->assertEquals('/home', $url->get('/home'));
        $this->assertEquals('/home', $url->get('home'));

        $this->assertEquals('/post/index', $url->get(['post/index']));
        $this->assertEquals('/post/view?id=100', $url->get(['post/view', 'id' => 100]));
        $this->assertEquals('/post/view?id=100#content', $url->get(['post/view', 'id' => 100, '#' => 'content']));

        $this->assertEquals('/home?from=google', $url->get(['/home', 'from' => 'google']));
        $this->assertEquals('/home?t=1&from=google', $url->get(['/home?t=1', 'from' => 'google']));
        $this->assertEquals('/article/10', $url->get(['/article/:article_id', 'article_id' => 10]));
        $this->assertEquals(
            '/article/10?from=google', $url->get(['/article/:article_id', 'article_id' => 10, 'from' => 'google'])
        );

        $this->_di->alias->set('@web', 'http://www.manaphp.com/manaphp');
        $url = new Url();

        $this->assertEquals('http://www.manaphp.com/manaphp/', $url->get('/'));
        $_SERVER['REQUEST_URI'] = '/a/b/c';
        $this->assertEquals('/a/b/c', $url->get(''));
        $this->assertEquals('http://www.manaphp.com/manaphp/home', $url->get('/home'));
        $this->assertEquals('/manaphp/home', $url->get('home'));

        $this->assertEquals(
            'http://www.manaphp.com/manaphp/home?from=google', $url->get(['/home', 'from' => 'google'])
        );
        $this->assertEquals(
            'http://www.manaphp.com/manaphp/home?t=1&from=google', $url->get(['/home?t=1', 'from' => 'google'])
        );
        $this->assertEquals(
            'http://www.manaphp.com/manaphp/article/10', $url->get(['/article/:article_id', 'article_id' => 10])
        );
        $this->assertEquals(
            'http://www.manaphp.com/manaphp/article/10?from=google',
            $url->get(['/article/:article_id', 'article_id' => 10, 'from' => 'google'])
        );
    }
}