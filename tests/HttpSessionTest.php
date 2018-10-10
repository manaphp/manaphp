<?php
namespace Tests;

use ManaPHP\Http\Session;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;

class HttpSessionTest extends TestCase
{

    public function setUp()
    {
        error_reporting(0);
        new Factory();
    }

    public function test_get()
    {
        $session = new Session();

        $this->assertFalse($session->has('some'));
        $session->set('some', 'value');
        $this->assertEquals('value', $session->get('some'));

        $this->assertFalse($session->has('some2'));
        $this->assertEquals('v', $session->get('some2', 'v'));
    }

    public function test_offsetGet()
    {
        $session = new Session();

        $session->set('some', 'value');
        $this->assertEquals('value', $session['some']);
    }

    public function test_set()
    {
        $session = new Session();

        $this->assertFalse($session->has('some'));
        $session->set('some', 'value');
        $this->assertEquals('value', $session->get('some'));
    }

    public function test_offsetSet()
    {
        $session = new Session();

        $this->assertFalse($session->has('some'));
        $session['some'] = 'value';
        $this->assertEquals('value', $session->get('some'));
    }

    public function test_has()
    {
        $session = new Session();

        $this->assertFalse($session->has('some'));

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));
    }

    public function test_offsetExists()
    {
        $session = new Session();

        $this->assertFalse(isset($session['some']));

        $session->set('some', 'value');
        $this->assertTrue(isset($session['some']));
    }

    public function test_destroy()
    {
        $session = new Session();

        $session->destroy();
    }

    public function test_remove()
    {
        $session = new Session();

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));

        $session->remove('some');
        $this->assertFalse($session->has('some'));
    }

    public function test_offsetUnset()
    {
        $session = new Session();

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));

        unset($session['some']);
        $this->assertFalse($session->has('some'));
    }
}