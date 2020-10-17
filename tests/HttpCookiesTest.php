<?php

namespace Tests;

use ManaPHP\Http\Cookies;
use ManaPHP\Security\Crypt;
use PHPUnit\Framework\TestCase;

class HttpCookiesTest extends TestCase
{
    /**
     * @var Cookies
     */
    protected $_cookies;

    public function setUp()
    {
        $this->_cookies = new Cookies();
        $this->_cookies->crypt = new Crypt('abc');
    }

    public function test_set()
    {
        $this->_cookies->delete('name');

        $this->assertFalse($this->_cookies->has('name'));

        $this->_cookies->set('name', 'mana');
        $this->assertTrue($this->_cookies->has('name'));
        $this->assertEquals('mana', $this->_cookies->get('name'));

        $this->_cookies->set('!name', 'mana');
        $this->assertEquals('mana', $this->_cookies->get('!name'));
    }

    public function test_get()
    {
        $this->_cookies->delete('name');
        $this->assertEquals(null, $this->_cookies->get('name'));

        $this->_cookies->set('name', 'mana');
        $this->assertEquals('mana', $this->_cookies->get('name'));

        $this->_cookies->set('!name', 'mana');
        $this->assertEquals('mana', $this->_cookies->get('!name'));
    }

    public function test_has()
    {
        $this->_cookies->delete('name');

        $this->assertFalse($this->_cookies->has('name'));

        $this->_cookies->set('name', 'mana');
        $this->assertTrue($this->_cookies->has('name'));

        $this->_cookies->set('!name', 'mana');
        $this->assertTrue($this->_cookies->has('!name'));
    }

    public function test_delete()
    {
        $this->assertFalse($this->_cookies->has('missing'));
        $this->_cookies->delete('missing');

        $this->_cookies->set('name', 'mana');
        $this->assertTrue($this->_cookies->has('name'));
        $this->_cookies->delete('name');
    }
}