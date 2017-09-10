<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 23:20
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class DummyComponent extends \ManaPHP\Component
{
    public function doAction()
    {
        $this->fireEvent('dummy:doAction', ['extra data']);
    }
}

class DummyListener
{
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    protected $_testCase;

    public function __construct($testCase)
    {
        $this->_testCase = $testCase;
    }

    public function doAction($component, $data)
    {
        $this->_testCase->assertInstanceOf('DummyComponent', $component);
        $this->_testCase->assertEquals($data, ['extra data']);
    }
}

class EventTest extends TestCase
{
    public function setUp()
    {
        new \ManaPHP\Di\FactoryDefault();
    }

    public function test_attachEvent()
    {
        //use class
        $component = new DummyComponent();
        $listener = new DummyListener($this);
        $component->attachEvent('dummy', $listener);
        $component->doAction();

        //use closure
        $component = new DummyComponent();
        $that = $this;
        $component->attachEvent('dummy:doAction', function ($source, $data) use ($that) {
            $that->assertInstanceOf('DummyComponent', $source);
            $that->assertEquals($data, ['extra data']);
        });
        $component->doAction();
    }
}