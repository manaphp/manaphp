<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Request;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
    protected $container;

    public function setUp()
    {
        parent::setUp();

        $this->container = new FactoryDefault();
    }

    public function test_input()
    {
        $request = new Request();

        $this->assertSame('', $request->input('name'));

        $this->assertEquals('test', $request->input('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->input('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Filter\Exception', $e);
        }

        $_REQUEST['name'] = 'mana';
        $this->assertEquals('mana', $request->input('name'));
    }

    public function test_query()
    {
        $request = new Request();

        $this->assertSame('', $request->query('name'));

        $this->assertEquals('test', $request->query('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->query('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Filter\Exception', $e);
        }

        $_GET['name'] = 'mana';
        $this->assertEquals('mana', $request->query('name'));
    }

    public function test_scheme()
    {
        $request = new Request();

        $this->assertEquals('http', $request->scheme());

        $_SERVER['HTTPS'] = 'off';
        $this->assertEquals('http', $request->scheme());

        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https', $request->scheme());
    }

    public function test_isAjax()
    {
        $request = new Request();
        $this->assertFalse($request->isAjax());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($request->isAjax());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'ABC';
        $this->assertFalse($request->isAjax());
    }

    public function test_ip()
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $request = new Request();
        $this->assertEquals('1.2.3.4', $request->ip());

        $_SERVER['HTTP_X_REAL_IP'] = '10.20.30.40';
        $request = new Request();
        $this->assertEquals('10.20.30.40', $request->ip());
    }

    public function test_files()
    {
        $request = new Request();

        $_FILES = array(
            'fieldNameHere' => array(
                'name'     => 'favicon.ico',
                'type'     => 'image/x-icon',
                'tmp_name' => '/tmp/php7F4.tmp',
                'error'    => 0,
                'size'     => 202575,
            ),
        );

        $files = $request->files();
        $this->assertCount(1, $files);

        $file = $files[0];
        $this->assertEquals('fieldNameHere', $file->getKey());
        $this->assertEquals('favicon.ico', $file->getName());
        $this->assertEquals('image/x-icon', $file->getType());
        $this->assertEquals('/tmp/php7F4.tmp', $file->getTempName());
        $this->assertEquals(0, $file->getError());
        $this->assertEquals(202575, $file->getSize());
        $this->assertEquals('ico', $file->getExtension());

        $_FILES = [
            'photo' => array(
                'name'     => [0 => 'f0', 1 => 'f1'],
                'type'     => [0 => 'text/plain', 1 => 'text/csv'],
                'tmp_name' => [0 => 't0', 1 => 't1'],
                'error'    => [0 => 0, 1 => UPLOAD_ERR_NO_FILE],
                'size'     => [0 => 10, 1 => 20],
            ),
        ];

        $all = $request->files(false);
        $successful = $request->files(true);
        $this->assertCount(2, $all);
        $this->assertCount(1, $successful);

        $this->assertEquals($all[0]->getName(), 'f0');
        $this->assertEquals($all[1]->getName(), 'f1');

        $this->assertEquals($all[0]->getTempName(), 't0');
        $this->assertEquals($all[1]->getTempName(), 't1');

        $this->assertEquals($successful[0]->getName(), 'f0');

        $this->assertEquals($successful[0]->getTempName(), 't0');
        $this->assertEquals($successful[0]->getExtension(), '');
    }

    public function test_getUrl()
    {
        $request = new Request();

        $base = [
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST'      => 'www.manaphp.com',
            'SERVER_PORT'    => '80',
            'REQUEST_URI'    => '/index.php'
        ];

        $_SERVER = $base;
        $this->assertEquals('http://www.manaphp.com/index.php', $request->url());

//        $_SERVER=$base;
//        $_SERVER['SERVER_PORT']='81';
//        $this->assertEquals('http://www.manaphp.com:81/index.php',$request->getUrl());
//
//        $_SERVER=$base;
//        $_SERVER['REQUEST_SCHEME']='https';
//        $_SERVER['SERVER_PORT']='443';
//        $this->assertEquals('https://www.manaphp.com/index.php',$request->getUrl());
//
//        $_SERVER=$base;
//        $_SERVER['REQUEST_SCHEME']='https';
//        $_SERVER['SERVER_PORT']='8080';
//        $this->assertEquals('https://www.manaphp.com:8080/index.php',$request->getUrl());
//
        $_SERVER = $base;
        $this->assertEquals('http://www.manaphp.com/index.php', $request->url());
    }

    public function test_getEmptyValue()
    {
        $request = new Request();

        $_REQUEST = [];
        $this->assertSame('', $request->input('k'));
        $this->assertNull($request->input('k', null, null));
        $this->assertSame('v', $request->input('k', null, 'v'));

        $_REQUEST = ['k' => ''];
        $this->assertSame('', $request->input('k'));
        $this->assertNull($request->input('k', null, null));
        $this->assertSame('v', $request->input('k', null, 'v'));
    }
}
