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
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $_di;

    public function setUp()
    {
        $this->_di = new Di();
    }

    public function test_set()
    {
        //class name string
        $this->_di->set('request1', 'ManaPHP\Http\Request');
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request1'));

        //anonymous function
        $this->_di->set('request2', function () {
            return new Request();
        });
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request2'));

        //anonymous function
        $this->_di->set('request3', function () {
            return new Request();
        })->setAliases('request3', ['request31', 'request32']);

        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request2'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request31'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request32'));
    }

    public function test_setShared()
    {
        //class name string
        $this->_di->setShared('request1', 'ManaPHP\Http\Request');
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request1'));

        //anonymous function
        $this->_di->setShared('request2', function () {
            return new Request();
        });
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request2'));

        //anonymous function
        $this->_di->setShared('set_request3', function () {
            return new Request();
        })->setAliases('set_request3', ['request31', 'request32']);
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request31'));
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('request32'));
    }

    public function test_has()
    {
        $this->_di->set('has_request', function () {
            return new Request();
        });

        $this->assertTrue($this->_di->has('has_request'));
        $this->assertFalse($this->_di->has('has_missing'));
    }

    public function test_getShared()
    {
        $this->_di->set('getSharedObject', function () {
            $object = new \stdClass();
            $object->microtime = md5(microtime(true) . mt_rand());
            return $object;
        });

        $getSharedObject = $this->_di->getShared('getSharedObject');
        $this->assertInstanceOf('stdClass', $getSharedObject);

        $getSharedObject2 = $this->_di->getShared('getSharedObject');
        $this->assertInstanceOf('stdClass', $getSharedObject2);

        $this->assertEquals($getSharedObject->microtime, $getSharedObject2->microtime);
    }

    public function test_get()
    {
        $this->_di->set('getComponent1', function ($v) {
            return new SomeComponent($v);
        });

        $this->_di->set('getComponent2', 'Tests\SomeComponent');

        $this->assertEquals(100, $this->_di->get('getComponent1', [100])->value);
        $this->assertEquals(50, $this->_di->get('getComponent2', [50])->value);
    }

    public function test_remove()
    {
        $this->_di->set('removeService', function () {
            return new \stdClass();
        });

        $this->assertTrue($this->_di->has('removeService'));

        $this->_di->remove('removeService');
        $this->assertFalse($this->_di->has('removeService'));
    }
}