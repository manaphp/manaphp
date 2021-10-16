<?php

namespace Tests;

use ManaPHP\Di\Container;
use ManaPHP\Http\Response;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new Factory();
    }

    public function test_setStatusCode()
    {
        $response = new Response();

        //set only time
        $response->setStatus(404, 'Not Found');
        $this->assertEquals('404 Not Found', $response->getStatus());

        //set multiple times
        $response = new Response();
        $response->setStatus(200, 'OK');
        $response->setStatus(404, 'Not Found');
        $response->setStatus(409, 'Conflict');
        $this->assertEquals('409 Conflict', $response->getStatus());
    }

    public function test_setHeader()
    {
        $response = new Response();

        $response->setHeader('Content-Type', 'text/html');
        $this->assertEquals(['Content-Type' => 'text/html'], $response->getHeaders());

        $response->setHeader('Content-Length', '1234');
        $this->assertEquals(
            [
                'Content-Type'   => 'text/html',
                'Content-Length' => '1234'
            ], $response->getHeaders()
        );
    }

    public function test_setExpires()
    {
        $response = new Response();

        date_default_timezone_set('PRC');

        $response->setExpires(strtotime('2015-12-18 00:12:42'));
        $this->assertEquals(['Expires' => 'Thu, 17 Dec 2015 16:12:42 GMT'], $response->getHeaders());
    }

    public function test_setNotModified()
    {
        $response = new Response();

        $response->setNotModified();

        $this->assertEquals('304 Not modified', $response->getStatus());
    }

    public function test_setContentType()
    {
        $response = new Response();

        $response->setContentType('application/json');
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());

        $response->setContentType('application/json', 'utf-8');
        $this->assertEquals(['Content-Type' => 'application/json; charset=utf-8'], $response->getHeaders());
    }

    public function test_redirect()
    {
        $response = new Response();
        $response->setContainer(new Container());

        $response->redirect('some/local/url');
        $this->assertEquals('302 Temporarily Moved', $response->getStatus());
        $this->assertEquals(['Location' => 'some/local/url'], $response->getHeaders());

        $response = new Response();
        $response->setContainer(new Container());

        $response->redirect('http://www.manaphp.com');
        $this->assertEquals('302 Temporarily Moved', $response->getStatus());
        $this->assertEquals(['Location' => 'http://www.manaphp.com'], $response->getHeaders());

        $response = new Response();
        $response->setContainer(new Container());

        $response->redirect('http://www.manaphp.com', false);
        $this->assertEquals('301 Permanently Moved', $response->getStatus());
        $this->assertEquals(['Location' => 'http://www.manaphp.com'], $response->getHeaders());

        $response = new Response();
        $response->setContainer(new Container());

        $response->redirect('http://www.manaphp.com', true);
        $this->assertEquals('302 Temporarily Moved', $response->getStatus());
        $this->assertEquals(['Location' => 'http://www.manaphp.com'], $response->getHeaders());
    }

    public function test_setContent()
    {
        $response = new Response();

        $response->setContent('<h1>Hello');
        $this->assertEquals('<h1>Hello', $response->getContent());

        $response->setContent('<h1>Hello 2');
        $this->assertEquals('<h1>Hello 2', $response->getContent());
    }

    public function test_setJsonContent()
    {
        $response = new Response();

        $response->setJsonContent(0);
        $this->assertEquals(['code' => 0, 'message' => ''], json_decode($response->getContent(), true));

        $response->setJsonContent(100);
        $this->assertEquals(['code' => 100, 'message' => ''], json_decode($response->getContent(), true));

        $response->setJsonContent(['name' => 'manaphp']);
        $this->assertEquals(
            ['code' => 0, 'message' => '', 'data' => ['name' => 'manaphp']], json_decode($response->getContent(), true)
        );

        $response->setJsonContent(['code' => 0, 'message' => 'OK']);
        $this->assertEquals(['code' => 0, 'message' => 'OK'], json_decode($response->getContent(), true));

        $response->setJsonContent('{"code":10,"message":"OK"}');
        $this->assertEquals(['code' => 10, 'message' => 'OK'], json_decode($response->getContent(), true));

        $response->setJsonContent(['code' => 0, 'message' => 'OK', 'data' => 'http://www.manaphp.com/tags/中国']);
        $this->assertEquals(
            ['code' => 0, 'message' => 'OK', 'data' => 'http://www.manaphp.com/tags/中国'],
            json_decode($response->getContent(), true)
        );
    }

    public function test_appendContent()
    {
        $response = new Response();

        $this->assertEquals('', $response->getContent());

        $response->appendContent('<h1>Hello');
        $this->assertEquals('<h1>Hello', $response->getContent());

        $response->appendContent('</h1>');
        $this->assertEquals('<h1>Hello</h1>', $response->getContent());
    }

    public function test_getContent()
    {
        $response = new Response();

        $response->setContent('Hello');
        $this->assertEquals('Hello', $response->getContent());
    }

    /**
     * @
     */
    public function test_setFile()
    {
        $response = new Response();

        $response->setFile(__FILE__);
        ob_start();
        $response->send();
        $this->assertStringEqualsFile(__FILE__, ob_get_clean());
    }
}