<?php

namespace Tests;

use ManaPHP\Di\Container;
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
    public function test_setShared()
    {
        //string
        $container = new Container();
        $container->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($container->getShared('request'), $container->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getShared('request'));

        $container = new Container();
        $container->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($container->getNew('request'), $container->getNew('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getNew('request'));

        //array
        $container = new Container();
        $container->setShared('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($container->getShared('request'), $container->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getShared('request'));

        $container = new Container();
        $container->setShared('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($container->getNew('request'), $container->getNew('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getNew('request'));

        //object
        $container = new Container();
        $container->setShared('request', new \ManaPHP\Http\Request());
        $this->assertSame($container->getShared('request'), $container->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getShared('request'));

        $container = new Container();
        $container->setShared('request', new \ManaPHP\Http\Request());
        $this->assertSame($container->getNew('request'), $container->getNew('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getNew('request'));

        //closure
        $container = new Container();
        $container->setShared(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($container->getShared('request'), $container->getShared('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getShared('request'));

        //closure
        $container = new Container();
        $container->setShared(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($container->getNew('request'), $container->getNew('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->getNew('request'));
    }

    public function test_has()
    {
        $container = new Container();
        $container->setShared('request', 'ManaPHP\Http\Request');
        $this->assertTrue($container->has('request'));
        $this->assertFalse($container->has('request_missing'));
    }

    public function test_getShared()
    {
        $container = new Container();
        $container->setShared('request', 'ManaPHP\Http\Request');
        $this->assertSame($container->getShared('request'), $container->getShared('request'));
    }

    public function test_get()
    {
        $container = new Container();

        $container->setShared('getComponent2', 'Tests\SomeComponent');

        $this->assertEquals(50, $container->getNew('getComponent2', [50])->value);
    }

    public function test_remove()
    {
        $container = new Container();
        $container->setShared(
            'removeService', function () {
            return new \stdClass();
        }
        );

        $this->assertTrue($container->has('removeService'));

        $container->remove('removeService');
        $this->assertFalse($container->has('removeService'));
    }
}