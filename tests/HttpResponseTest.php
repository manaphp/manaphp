<?php
namespace Tests;

use ManaPHP\Di;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Response;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->setShared('configure', 'ManaPHP\Configure\Configure');

    }

    public function test_setStatusCode()
    {
        $response = new Response();

        //set only time
        $response->setStatusCode(404, 'Not Found');
        $this->assertEquals(['Status' => '404 Not Found'], $response->getHeaders());

        //set multiple times
        $response = new Response();
        $response->setStatusCode(200, 'OK');
        $response->setStatusCode(404, 'Not Found');
        $response->setStatusCode(409, 'Conflict');
        $this->assertEquals(['Status' => '409 Conflict'], $response->getHeaders());
    }

    public function test_setHeader()
    {
        $response = new Response();

        $response->setHeader('Content-Type', 'text/html');
        $this->assertEquals(['Content-Type' => 'text/html'], $response->getHeaders());

        $response->setHeader('Content-Length', '1234');
        $this->assertEquals([
            'Content-Type' => 'text/html',
            'Content-Length' => '1234'
        ], $response->getHeaders());
    }

    public function test_setRawHeader()
    {
        $response = new Response();

        $response->setRawHeader('Server: Apache');
        $this->assertEquals(['Server: Apache' => ''], $response->getHeaders());
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

        $this->assertEquals(['Status' => '304 Not modified'], $response->getHeaders());
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
        $response->setDependencyInjector(new Di());

        $response->redirect('some/local/url');
        $this->assertEquals([
            'Status' => '302 Temporarily Moved',
            'Location' => 'some/local/url'
        ], $response->getHeaders());

        $response = new Response();
        $response->setDependencyInjector(new Di());

        $response->redirect('http://www.manaphp.com');
        $this->assertEquals([
            'Status' => '302 Temporarily Moved',
            'Location' => 'http://www.manaphp.com'
        ], $response->getHeaders());

        $response = new Response();
        $response->setDependencyInjector(new Di());

        $response->redirect('http://www.manaphp.com', false);
        $this->assertEquals([
            'Status' => '301 Permanently Moved',
            'Location' => 'http://www.manaphp.com'
        ], $response->getHeaders());

        $response = new Response();
        $response->setDependencyInjector(new Di());

        $response->redirect('http://www.manaphp.com', false);
        $this->assertEquals([
            'Status' => '301 Permanently Moved',
            'Location' => 'http://www.manaphp.com'
        ], $response->getHeaders());
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

        $response->setJsonContent(['code' => 0, 'message' => 'OK']);
        $this->assertEquals(['code' => 0, 'message' => 'OK'], json_decode($response->getContent(), true));

        $response->setJsonContent(['code' => 0, 'message' => 'OK', 'data' => 'http://www.manaphp.com/tags/中国'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->assertEquals(['code' => 0, 'message' => 'OK', 'data' => 'http://www.manaphp.com/tags/中国'],
            json_decode($response->getContent(), true));
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
    public function test_setFileToSend()
    {
        $response = new Response();

        $response->setFileToSend(__FILE__);
        ob_start();
        $response->send();
        $this->assertStringEqualsFile(__FILE__, ob_get_clean());
        $this->assertTrue($response->isSent());
    }
}