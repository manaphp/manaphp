<?php

namespace Tests;

use ManaPHP\Exception;
use ManaPHP\Http\Dispatcher;
use ManaPHP\Mvc\Factory;
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
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        parent::setUp();

        $this->container = new Factory();

        $this->container->getShared('alias')->set('@ns.module', 'App\\Test');
        $this->container->remove($this->container->getShared('alias')->resolve('@ns.module\\Controllers\\Test1Controller'));
        $this->container->remove($this->container->getShared('alias')->resolve('@ns.module\\Controllers\\Test2Controller'));
    }

    public function test_dispatch()
    {
        $dispatcher = $this->container->dispatcher;

        //camelize the handler class:not require
        try {
            $dispatcher->dispatch('Test', 'Index', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\IndexController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,only one word
        try {
            $dispatcher->dispatch('Test', 'missing', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\MissingController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,multiple words
        try {
            $dispatcher->dispatch('Test', 'test_home', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals('`App\Test\Controllers\TestHomeController` class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Dispatcher\NotFoundControllerException', $e);
        }

        //action determination

        try {
            $dispatcher->dispatch('Test', 'test1', 'index');
            $this->fail('why not?');
        } catch (Exception $e) {
            $this->assertEquals(
                '`App\Test\Controllers\Test1Controller::indexAction` is not found, action is case sensitive.',
                $e->getMessage()
            );
        }

        //normal usage without return value
        $dispatcher->dispatch('Test', 'test2', 'other');
        $this->assertInstanceOf('App\\Test\\Controllers\\Test2Controller', $dispatcher->getControllerInstance());
        $this->assertEquals('other', $dispatcher->getAction());
        $this->assertNull($dispatcher->getReturnedValue());

        //normal usage with return value
        $dispatcher->dispatch('Test', 'test2', 'another');
        $this->assertEquals(100, $dispatcher->getReturnedValue());

//        //bind param to method parameter
//        $dispatcher->dispatch('Test', 'test2', 'another2', [2, '3']);
//        $this->assertEquals(5, $dispatcher->getReturnedValue());


        //fetch param from dispatcher
        $dispatcher->dispatch('Test', 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());

        //inherit class
        $dispatcher->dispatch('Test', 'test7', 'service');
        $this->assertEquals('hello', $dispatcher->getReturnedValue());

        $this->assertEquals(strtolower('test7'), strtolower($dispatcher->getController()));
    }

    public function test_getReturnedValue()
    {
        $dispatcher = $this->container->dispatcher;
        $dispatcher->dispatch('Test', 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());
    }
}