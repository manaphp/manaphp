<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/15
 * Time: 20:35
 */

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

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
        $this->_di = new ManaPHP\Di();
    }

    public function test_set()
    {
        //class name string
        $this->_di->set('set_request1', 'ManaPHP\Http\Request');
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('set_request1'));

        //anonymous function
        $this->_di->set('set_request2', function () {
            return new ManaPHP\Http\Request();
        });
        $this->assertInstanceOf('ManaPHP\Http\Request', $this->_di->get('set_request2'));
    }

    public function test_has()
    {
        $this->_di->set('has_request', function () {
            return new ManaPHP\Http\Request();
        });

        $this->assertTrue($this->_di->has('has_request'));
        $this->assertFalse($this->_di->has('has_missing'));
    }

    public function test_getShared()
    {
        $this->_di->set('getSharedObject', function () {
            $object = new stdClass();
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

        $this->_di->set('getComponent2', 'SomeComponent');

        $this->assertEquals(100, $this->_di->get('getComponent1', [100])->value);
        $this->assertEquals(50, $this->_di->get('getComponent2', [50])->value);
    }

    public function test_remove()
    {
        $this->_di->set('removeService', function () {
            return new stdClass();
        });

        $this->assertTrue($this->_di->has('removeService'));

        $this->_di->remove('removeService');
        $this->assertFalse($this->_di->has('removeService'));
    }
}