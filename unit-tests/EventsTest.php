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
        $this->fireEvent('dummy:doAction', 'extra data');
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

    public function doAction(\ManaPHP\Event\Event $event, $component, $data)
    {
        $this->_testCase->assertEquals('doAction', $event->getType());
        $this->_testCase->assertInstanceOf('ManaPHP\Event\Event', $event);
        $this->_testCase->assertInstanceOf('DummyComponent', $component);
        $this->_testCase->assertEquals($data, 'extra data');
    }
}

class EventsTest extends TestCase
{
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
        $component->attachEvent('dummy:doAction', function (\ManaPHP\Event\Event $event, $source, $data) use ($that) {
            $that->assertEquals('doAction', $event->getType());
            $that->assertInstanceOf('ManaPHP\Event\Event', $event);
            $that->assertInstanceOf('DummyComponent', $source);
            $that->assertEquals($data, 'extra data');
        });
        $component->doAction();
    }
}