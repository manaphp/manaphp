<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/13
 * Time: 21:57
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';
require __DIR__.'/Dispatcher/Controllers.php';

class tDispatcher extends \ManaPHP\Mvc\Dispatcher
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

    public function test_setRootNamespace()
    {
        $dispatcher = new tDispatcher();

        $dispatcher->setRootNamespace('Application\Api');
        $this->assertEquals('Application\Api', $dispatcher->getRootNamespace());

        $this->assertInstanceOf('\ManaPHP\Mvc\Dispatcher', $dispatcher->setRootNamespace('Application\Api'));
        $this->assertInstanceOf('tDispatcher', $dispatcher->setRootNamespace('Application\Api'));
    }

    public function test_setNamespaceName()
    {
        $dispatcher = new tDispatcher();

        $dispatcher->setRootNamespace('Application\Api');
        $this->assertEquals('Application\Api', $dispatcher->getRootNamespace());

        $this->assertInstanceOf('\ManaPHP\Mvc\Dispatcher', $dispatcher->setRootNamespace('Application\Api'));
        $this->assertInstanceOf('tDispatcher', $dispatcher->setRootNamespace('Application\Api'));
    }

    public function test_dispatch()
    {
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setDependencyInjector($di);
        $dispatcher->setRootNamespace('App');

        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        //camelize the handler class:not require
        try {
            $dispatcher->dispatch('Test', 'Index', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('App\\Test\Controllers\\IndexController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,only one word
        try {
            $dispatcher->dispatch('Test', 'missing', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('App\\Test\\Controllers\\MissingController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //camelize the handler class: require,multiple words
        try {
            $dispatcher->dispatch('Test', 'test_home', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('App\\Test\\Controllers\\TestHomeController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\NotFoundControllerException', $e);
        }

        //action determination

        try {
            $dispatcher->dispatch('Test', 'test1', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals("Action 'indexAction' was not found on handler 'App\\Test\\Controllers\\Test1Controller'", $e->getMessage());
        }

        //normal usage without return value
        $controller = $dispatcher->dispatch('Test', 'test2', 'other');
        $this->assertInstanceOf('App\\Test\\Controllers\\Test2Controller', $controller);
        $this->assertEquals('other', $dispatcher->getActionName());
        $this->assertNull($dispatcher->getReturnedValue());

        //normal usage with return value
        $dispatcher->dispatch('Test', 'test2', 'another');
        $this->assertEquals(100, $dispatcher->getReturnedValue());

        //bind param to method parameter
        $dispatcher->dispatch('Test', 'test2', 'another2', [2, '3']);
        $this->assertEquals(5, $dispatcher->getReturnedValue());

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
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setRootNamespace('App');

        $dispatcher->setDependencyInjector($di);
        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        $dispatcher->dispatch('Test', 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());
    }

    public function test_forward()
    {
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setDependencyInjector($di);
        $dispatcher->setRootNamespace('App');

        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

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
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setDependencyInjector($di);
        $dispatcher->setRootNamespace('App');

        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        $dispatcher->dispatch('Test', 'test2', 'another3');
        $this->assertTrue($dispatcher->wasForwarded());
    }
}