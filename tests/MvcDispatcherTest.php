<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Exception;
use ManaPHP\Mvc\Dispatcher;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/Dispatcher/Controllers.php';

class tDispatcher extends Dispatcher
{
    protected function _handleException($exception)
    {

    }

    protected function _throwDispatchException($message, $exceptionCode = 0)
    {

    }

    public function getActionSuffix()
    {
        return $this->_actionSuffix;
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }
}

class MvcDispatcherTest extends TestCase
{
    /**
     * @var \ManaPHP\Di
     */
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new FactoryDefault();

        $this->_di->alias->set('@ns.module', 'App\\Test');
        $this->_di->remove($this->_di->alias->resolve('@ns.module\\Controllers\\Test1Controller'));
        $this->_di->remove($this->_di->alias->resolve('@ns.module\\Controllers\\Test2Controller'));
    }

    public function test_dispatch()
    {
        $dispatcher = $this->_di->dispatcher;

        //camelize the handler class:not require
        try {
            $dispatcher->dispatch('Test', 'Index', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\IndexController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,only one word
        try {
            $dispatcher->dispatch('Test', 'missing', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\MissingController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,multiple words
        try {
            $dispatcher->dispatch('Test', 'test_home', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\TestHomeController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //action determination

        try {
            $dispatcher->dispatch('Test', 'test1', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`indexAction` action was not found on `App\Test\Controllers\Test1Controller`', $e->getMessage());
        }

        //normal usage without return value
        $dispatcher->dispatch('Test', 'test2', 'other');
        $this->assertInstanceOf('App\\Test\\Controllers\\Test2Controller', $dispatcher->getController());
        $this->assertEquals('other', $dispatcher->getActionName());
        $this->assertNull($dispatcher->getReturnedValue());

        //normal usage with return value
        $dispatcher->dispatch('Test', 'test2', 'another');
        $this->assertEquals(100, $dispatcher->getReturnedValue());

//        //bind param to method parameter
//        $dispatcher->dispatch('Test', 'test2', 'another2', [2, '3']);
//        $this->assertEquals(5, $dispatcher->getReturnedValue());

        //forward
        $dispatcher->dispatch('Test', 'test2', 'another3');
        $this->assertEquals('another4', $dispatcher->getActionName());
        $this->assertEquals(120, $dispatcher->getReturnedValue());
        $this->assertTrue($dispatcher->wasForwarded());

        //fetch param from dispatcher
        $dispatcher->dispatch('Test', 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());

        //inherit class
        $dispatcher->dispatch('Test', 'test7', 'service');
        $this->assertEquals('hello', $dispatcher->getReturnedValue());

        $this->assertEquals(strtolower('test7'), strtolower($dispatcher->getControllerName()));
    }

    public function test_getReturnedValue()
    {
        $dispatcher = $this->_di->dispatcher;
        $dispatcher->dispatch('Test', 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());
    }

    public function test_forward()
    {
        $dispatcher = $this->_di->dispatcher;

        $dispatcher->dispatch('Test', 'test2', 'another3');
        $this->assertEquals(strtolower('test2'), strtolower($dispatcher->getPreviousControllerName()));
        $this->assertEquals('another3', $dispatcher->getPreviousActionName());
        $this->assertEquals(strtolower('test2'), strtolower($dispatcher->getControllerName()));
        $this->assertEquals('another4', $dispatcher->getActionName());

        $dispatcher->dispatch('Test', 'test2', 'index');
        $dispatcher->forward('test3/other');
        $this->assertEquals(strtolower('test3'), strtolower($dispatcher->getControllerName()));
        $this->assertEquals('other', $dispatcher->getActionName());

        $this->assertEquals(strtolower('test2'), strtolower($dispatcher->getPreviousControllerName()));
        $this->assertEquals('index', $dispatcher->getPreviousActionName());
    }

    public function test_wasForwarded()
    {
        $dispatcher = $this->_di->dispatcher;

        $dispatcher->dispatch('Test', 'test2', 'another3');
        $this->assertTrue($dispatcher->wasForwarded());
    }
}