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
    public function test_set()
    {
        //string
        $container = new Container();
        $container->set('request', 'ManaPHP\Http\Request');
        $this->assertSame($container->get('request'), $container->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->get('request'));

        //array
        $container = new Container();
        $container->set('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($container->get('request'), $container->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->get('request'));

        $container = new Container();
        $container->set('request', ['class' => 'ManaPHP\Http\Request']);
        $this->assertSame($container->make('request'), $container->make('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->make('request'));

        //object
        $container = new Container();
        $container->set('request', new \ManaPHP\Http\Request());
        $this->assertSame($container->get('request'), $container->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->get('request'));

        $container = new Container();
        $container->set('request', new \ManaPHP\Http\Request());
        $this->assertSame($container->make('request'), $container->make('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->make('request'));

        //closure
        $container = new Container();
        $container->set(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($container->get('request'), $container->get('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->get('request'));

        //closure
        $container = new Container();
        $container->set(
            'request', function () {
            return new \ManaPHP\Http\Request();
        }
        );
        $this->assertSame($container->make('request'), $container->make('request'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $container->make('request'));
    }

    public function test_has()
    {
        $container = new Container();
        $container->set('request', 'ManaPHP\Http\Request');
        $this->assertTrue($container->has('request'));
        $this->assertFalse($container->has('request_missing'));
    }

    public function test_get()
    {
        $container = new Container();
        $container->set('request', 'ManaPHP\Http\Request');
        $this->assertSame($container->get('request'), $container->get('request'));
    }

    public function test_make()
    {
        $container = new Container();

        $container->set('getComponent2', 'Tests\SomeComponent');

        $this->assertEquals(50, $container->make('getComponent2', [50])->value);
    }

    public function test_remove()
    {
        $container = new Container();
        $container->set(
            'removeService', function () {
            return new \stdClass();
        }
        );

        $this->assertTrue($container->has('removeService'));

        $container->remove('removeService');
        $this->assertFalse($container->has('removeService'));
    }
}