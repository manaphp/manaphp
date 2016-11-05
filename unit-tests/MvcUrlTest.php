<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';


class MvcUrlTest extends TestCase
{
    public function test_get()
    {
        $url = new \ManaPHP\Mvc\Url(['baseUrls'=>[''=>'/']]);
        $this->assertEquals('/', $url->get('/'));
        $this->assertEquals('/home', $url->get('/home'));
        $this->assertEquals('home', $url->get('home'));
        $this->assertEquals('//www.manaphp.com', $url->get('//www.manaphp.com'));
        $this->assertEquals('http://www.manaphp.com', $url->get('http://www.manaphp.com'));

        $this->assertEquals('/home?from=google', $url->get('/home', ['from' => 'google']));
        $this->assertEquals('/home?t=1&from=google', $url->get('/home?t=1', ['from' => 'google']));
        $this->assertEquals('/article/10', $url->get('/article/:article_id', ['article_id' => 10]));
        $this->assertEquals('/article/10?from=google', $url->get('/article/:article_id', ['article_id' => 10, 'from' => 'google']));

        $url = new \ManaPHP\Mvc\Url(['baseUrls'=>[''=>'/manaphp']]);
        $this->assertEquals('/manaphp/', $url->get('/'));
        $this->assertEquals('/manaphp/home', $url->get('/home'));
        $this->assertEquals('home', $url->get('home'));
        $this->assertEquals('http://www.manaphp.com', $url->get('http://www.manaphp.com'));

        $this->assertEquals('/manaphp/home?from=google', $url->get('/home', ['from' => 'google']));
        $this->assertEquals('/manaphp/home?t=1&from=google', $url->get('/home?t=1', ['from' => 'google']));
        $this->assertEquals('/manaphp/article/10', $url->get('/article/:article_id', ['article_id' => 10]));
        $this->assertEquals('/manaphp/article/10?from=google', $url->get('/article/:article_id', ['article_id' => 10, 'from' => 'google']));
    }
}