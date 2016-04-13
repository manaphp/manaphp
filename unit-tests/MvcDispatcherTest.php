<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/13
 * Time: 21:57
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class Test1Controller extends \ManaPHP\Mvc\Controller
{

}

class Test2Controller extends \ManaPHP\Mvc\Controller
{
    public function indexAction()
    {
    }

    public function otherAction()
    {

    }

    public function anotherAction()
    {
        return 100;
    }

    public function another2Action($a, $b)
    {
        return $a + $b;
    }

    public function another3Action()
    {
        return $this->dispatcher->forward(array(
            'controller' => 'test2',
            'action' => 'another4'
        ));
    }

    public function another4Action()
    {
        return 120;
    }

    public function another5Action()
    {
        return $this->dispatcher->getParam('param1') + $this->dispatcher->getParam('param2');
    }

}

class Test4Controller extends \ManaPHp\Mvc\Controller
{
    public function requestAction()
    {
        return $this->request->getPost('email', 'email');
    }

    public function viewAction()
    {
        return $this->view->setParamToView('born', 'this');
    }
}

class ControllerBase extends \ManaPHP\Mvc\Controller
{
    public function serviceAction()
    {
        return 'hello';
    }

}

class Test5Controller extends ManaPHP\Mvc\Controller
{
    public function notFoundAction()
    {
        return 'not-found';
    }

}

class Test6Controller extends ManaPHP\Mvc\Controller
{

}

/** @noinspection LongInheritanceChainInspection */
class Test7Controller extends ControllerBase
{

}

class Test8Controller extends ManaPHP\Mvc\Controller
{
    public function buggyAction()
    {
        throw new \ManaPHP\Exception('This is an uncaught exception');
    }

}

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
        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        //camelize the handler class:not require
        try {
            $dispatcher->dispatch('app', 'Index', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('IndexController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\Exception', $e);
        }

        //camelize the handler class: require,only one word
        try {
            $dispatcher->dispatch(null, 'missing', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('MissingController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\Exception', $e);
        }

        //camelize the handler class: require,multiple words
        try {
            $dispatcher->dispatch(null, 'test_home', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals('TestHomeController handler class cannot be loaded', $e->getMessage());
            $this->assertInstanceOf('ManaPHP\Mvc\Dispatcher\Exception', $e);
        }

        //action determination

        try {
            $dispatcher->dispatch(null, 'test1', 'index');
            $this->fail('why not?');
        } catch (\Manaphp\Exception $e) {
            $this->assertEquals("Action 'index' was not found on handler 'Test1Controller'", $e->getMessage());
        }

        //normal usage without return value
        $controller = $dispatcher->dispatch(null, 'test2', 'other');
        $this->assertInstanceOf('Test2Controller', $controller);
        $this->assertEquals('other', $dispatcher->getActionName());
        $this->assertNull($dispatcher->getReturnedValue());

        //normal usage with return value
        $dispatcher->dispatch(null, 'test2', 'another');
        $this->assertEquals(100, $dispatcher->getReturnedValue());

        //bind param to method parameter
        $dispatcher->dispatch(null, 'test2', 'another2', [2, '3']);
        $this->assertEquals(5, $dispatcher->getReturnedValue());

        //forward
        $dispatcher->dispatch(null, 'test2', 'another3');
        $this->assertEquals('another4', $dispatcher->getActionName());
        $this->assertEquals(120, $dispatcher->getReturnedValue());
        $this->assertTrue($dispatcher->wasForwarded());

        //fetch param from dispatcher
        $dispatcher->dispatch(null, 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());

        //inherit class
        $dispatcher->dispatch(null, 'test7', 'service');
        $this->assertEquals('hello', $dispatcher->getReturnedValue());

        $this->assertEquals(strtolower('test7'), strtolower($dispatcher->getControllerName()));
    }

    public function test_getReturnedValue()
    {
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setDependencyInjector($di);
        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        $dispatcher->dispatch(null, 'test2', 'another5', ['param1' => 2, 'param2' => 3]);
        $this->assertEquals(5, $dispatcher->getReturnedValue());
    }

    public function test_forward()
    {
        $di = new ManaPHP\Di();
        $di->set('response', new ManaPHP\Http\Response());

        $dispatcher = new ManaPHP\Mvc\Dispatcher();
        $dispatcher->setDependencyInjector($di);
        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        $dispatcher->dispatch(null, 'test2', 'another3');
        $this->assertEquals(strtolower('test2'), strtolower($dispatcher->getPreviousControllerName()));
        $this->assertEquals('another3', $dispatcher->getPreviousActionName());
        $this->assertEquals(strtolower('test2'), strtolower($dispatcher->getControllerName()));
        $this->assertEquals('another4', $dispatcher->getActionName());

        $dispatcher->dispatch(null, 'test2', 'index');
        $dispatcher->forward(['controller' => 'test3', 'action' => 'other']);
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
        $this->assertInstanceOf('\ManaPHP\Di', $dispatcher->getDependencyInjector());
        $di->set('dispatcher', $dispatcher);

        $dispatcher->dispatch(null, 'test2', 'another3');
        $this->assertTrue($dispatcher->wasForwarded());
    }

}