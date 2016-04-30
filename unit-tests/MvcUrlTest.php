<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MvcUrlTest extends TestCase
{

    public function test_setPrefix()
    {
        $url = new \ManaPHP\Mvc\Url();
        $this->assertEquals('', $url->setPrefix('')->getPrefix());
        $this->assertEquals('', $url->setPrefix('/')->getPrefix());
        $this->assertEquals('/manaphp', $url->setPrefix('/manaphp')->getPrefix());
        $this->assertEquals('/manaphp', $url->setPrefix('/manaphp/')->getPrefix());
    }

    public function test_get()
    {
        $url = new \ManaPHP\Mvc\Url();

        $url->setPrefix('/');

        $this->assertEquals('/', $url->get('/'));
        $this->assertEquals('/home', $url->get('/home'));
        $this->assertEquals('home', $url->get('home'));
        $this->assertEquals('//www.manaphp.com', $url->get('//www.manaphp.com'));
        $this->assertEquals('http://www.manaphp.com', $url->get('http://www.manaphp.com'));

        $this->assertEquals('/home?from=google', $url->get('/home', ['from' => 'google']));
        $this->assertEquals('/home?t=1&from=google', $url->get('/home?t=1', ['from' => 'google']));
        $this->assertEquals('/article/10', $url->get('/article/{article_id}', ['article_id' => 10]));
        $this->assertEquals('/article/10?from=google', $url->get('/article/{article_id}', ['article_id' => 10, 'from' => 'google']));

        $url->setPrefix('/manaphp');

        $this->assertEquals('/manaphp/', $url->get('/'));
        $this->assertEquals('/manaphp/home', $url->get('/home'));
        $this->assertEquals('home', $url->get('home'));
        $this->assertEquals('//www.manaphp.com', $url->get('//www.manaphp.com'));
        $this->assertEquals('http://www.manaphp.com', $url->get('http://www.manaphp.com'));

        $this->assertEquals('/manaphp/home?from=google', $url->get('/home', ['from' => 'google']));
        $this->assertEquals('/manaphp/home?t=1&from=google', $url->get('/home?t=1', ['from' => 'google']));
        $this->assertEquals('/manaphp/article/10', $url->get('/article/{article_id}', ['article_id' => 10]));
        $this->assertEquals('/manaphp/article/10?from=google', $url->get('/article/{article_id}', ['article_id' => 10, 'from' => 'google']));
    }

    public function test_getJs()
    {
        $url = new \ManaPHP\Mvc\Url();
        $url->configure = new \ManaPHP\Configure\Configure();

        $url->setPrefix('/manaphp');

        $url->configure->debug = true;
        $this->assertEquals('/manaphp/app.js', $url->getJs('/app.js'));
        $this->assertEquals('/manaphp/app.js', $url->getJs('/app.js', true));
        $this->assertEquals('/manaphp/app.js', $url->getJs('/app.js', 'http://cdn.manaphp.com/app.js'));

        $url->configure->debug = false;
        $this->assertEquals('/manaphp/app.min.js', $url->getJs('/app.js'));
        $this->assertEquals('/manaphp/app.js', $url->getJs('/app.js', false));
        $this->assertEquals('http://cdn.manaphp.com/app.js', $url->getJs('/app.js', 'http://cdn.manaphp.com/app.js'));
    }

    public function test_getCss()
    {
        $url = new \ManaPHP\Mvc\Url();
        $url->configure = new \ManaPHP\Configure\Configure();

        $url->setPrefix('/manaphp');

        $url->configure->debug = true;
        $this->assertEquals('/manaphp/app.css', $url->getCss('/app.css'));
        $this->assertEquals('/manaphp/app.css', $url->getCss('/app.css', true));
        $this->assertEquals('/manaphp/app.css', $url->getCss('/app.css', 'http://cdn.manaphp.com/app.css'));

        $url->configure->debug = false;
        $this->assertEquals('/manaphp/app.min.css', $url->getCss('/app.css'));
        $this->assertEquals('/manaphp/app.css', $url->getCss('/app.css', false));
        $this->assertEquals('http://cdn.manaphp.com/app.css', $url->getCss('/app.css', 'http://cdn.manaphp.com/app.css'));
    }
}