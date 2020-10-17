<?php

namespace Tests;

use ManaPHP\Di;
use ManaPHP\Http\Request;
use PHPUnit\Framework\TestCase;

class SimpleComponent
{

}

class SomeComponent
{
    public $value = false;

    public function __construct($v)
    {
        $this->value = $v;
    }
}

class DiTest extends TestCase
{
    public function test_set()
    {
        //string
        $di = new Di();
        $di->set('request', 'ManaPHP\Http\Request');
        $this->assertNotSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //string
        $di = new Di();
        $di->set('request', 'ManaPHP\Http\Request');
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //array
        $di = new Di();
        $di->set('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertNotSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //array
        $di = new Di();
        $di->set('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        //array
        $di = new Di();
        $di->set('request', ['class' => 'ManaPHP\Http\Request', 'shared' => false]);
        $this->assertNotSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //object
        $di = new Di();
        $di->set('request', new \ManaPHP\Http\Request());
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        $di = new Di();
        $di->set('request', new \ManaPHP\Http\Request());
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //closure
        $di = new Di();
        $di->set(
            'request', function () {
            return new Request();
        }
        );
        $this->assertNotSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        $di = new Di();
        $di->set(
            'request', function () {
            return new Request();
        }
        );
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));
    }

    public function test_setShared()
    {
        //string
        $di = new Di();
        $di->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        $di = new Di();
        $di->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //array
        $di = new Di();
        $di->setShared('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        $di = new Di();
        $di->setShared('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //array
        $di = new Di();
        $di->setShared('request', ['class' => 'ManaPHP\Http\Request', 'shared' => true]);
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        //array
        $di = new Di();
        $di->setShared('request', ['class' => 'ManaPHP\Http\Request', 'shared' => true]);
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //object
        $di = new Di();
        $di->setShared('request', new \ManaPHP\Http\Request());
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        $di = new Di();
        $di->setShared('request', new \ManaPHP\Http\Request());
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));

        //closure
        $di = new Di();
        $di->setShared(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($di->getShared('request'), $di->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->getShared('request'));

        //closure
        $di = new Di();
        $di->setShared(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $di->get('request'));
    }

    public function test_has()
    {
        $di = new Di();
        $di->set('request', 'ManaPHP\Http\Request');
        $this->assertTrue($di->has('request'));
        $this->assertFalse($di->has('request_missing'));
    }

    public function test_getShared()
    {
        $di = new Di();
        $di->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($di->getShared('request'), $di->getShared('request'));

        $di = new Di();
        $di->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($di->get('request'), $di->get('request'));
    }

    public function test_get()
    {
        $di = new Di();
        $di->set(
            'getComponent1', function ($v) {
            return new SomeComponent($v);
        }
        );

        $di->set('getComponent2', 'Tests\SomeComponent');

        $this->assertEquals(100, $di->get('getComponent1', [100])->value);
        $this->assertEquals(50, $di->get('getComponent2', [50])->value);
    }

    public function test_remove()
    {
        $di = new Di();
        $di->set(
            'removeService', function () {
            return new \stdClass();
        }
        );

        $this->assertTrue($di->has('removeService'));

        $di->remove('removeService');
        $this->assertFalse($di->has('removeService'));
    }
}