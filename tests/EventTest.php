<?php

namespace Tests;

use ManaPHP\Component;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class DummyComponent extends Component
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
        $this->_testCase->assertInstanceOf('Tests\DummyComponent', $component);
        $this->_testCase->assertEquals($data, ['extra data']);
    }
}

class EventTest extends TestCase
{
    public function setUp()
    {
        new FactoryDefault();
    }

    public function test_attachEvent()
    {
        //use class
        $component = new DummyComponent();
        $listener = new DummyListener($this);
        $component->attachEvent('dummy:do', $listener);
        $component->doAction();

        //use closure
        $component = new DummyComponent();
        $that = $this;
        $component->attachEvent(
            'dummy:doAction', function ($source, $data) use ($that) {
            $that->assertInstanceOf('Tests\DummyComponent', $source);
            $that->assertEquals($data, ['extra data']);
        }
        );
        $component->doAction();
    }
}