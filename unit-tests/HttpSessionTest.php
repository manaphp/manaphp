<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 16:12
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class HttpSessionTest extends TestCase
{

    public function setUp()
    {
        error_reporting(0);
    }

    public function test_get()
    {
        $session = new \ManaPHP\Http\Session();

        $this->assertFalse($session->has('some'));
        $session->set('some', 'value');
        $this->assertEquals('value', $session->get('some'));

        $this->assertFalse($session->has('some2'));
        $this->assertEquals('v', $session->get('some2', 'v'));
    }

    public function test_offsetGet()
    {
        $session = new \ManaPHP\Http\Session();

        $session->set('some', 'value');
        $this->assertEquals('value', $session['some']);
    }

    public function test_set()
    {
        $session = new \ManaPHP\Http\Session();

        $this->assertFalse($session->has('some'));
        $session->set('some', 'value');
        $this->assertEquals('value', $session->get('some'));
    }

    public function test_offsetSet()
    {
        $session = new \ManaPHP\Http\Session();

        $this->assertFalse($session->has('some'));
        $session['some'] = 'value';
        $this->assertEquals('value', $session->get('some'));
    }

    public function test_has()
    {
        $session = new \ManaPHP\Http\Session();

        $this->assertFalse($session->has('some'));

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));
    }

    public function test_offsetExists()
    {
        $session = new \ManaPHP\Http\Session();

        $this->assertFalse(isset($session['some']));

        $session->set('some', 'value');
        $this->assertTrue(isset($session['some']));
    }

    public function test_destroy()
    {
        $session = new \ManaPHP\Http\Session();

        $session->destroy();
    }

    public function test_remove()
    {
        $session = new \ManaPHP\Http\Session();

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));

        $session->remove('some');
        $this->assertFalse($session->has('some'));
    }

    public function test_offsetUnset()
    {
        $session = new \ManaPHP\Http\Session();

        $session->set('some', 'value');
        $this->assertTrue($session->has('some'));

        unset($session['some']);
        $this->assertFalse($session->has('some'));
    }
}