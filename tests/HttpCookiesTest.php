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
    protected $cookies;

    public function setUp()
    {
        $this->cookies = new Cookies();
        $this->cookies->crypt = new Crypt('abc');
    }

    public function test_set()
    {
        $this->cookies->delete('name');

        $this->assertFalse($this->cookies->has('name'));

        $this->cookies->set('name', 'mana');
        $this->assertTrue($this->cookies->has('name'));
        $this->assertEquals('mana', $this->cookies->get('name'));

        $this->cookies->set('!name', 'mana');
        $this->assertEquals('mana', $this->cookies->get('!name'));
    }

    public function test_get()
    {
        $this->cookies->delete('name');
        $this->assertEquals(null, $this->cookies->get('name'));

        $this->cookies->set('name', 'mana');
        $this->assertEquals('mana', $this->cookies->get('name'));

        $this->cookies->set('!name', 'mana');
        $this->assertEquals('mana', $this->cookies->get('!name'));
    }

    public function test_has()
    {
        $this->cookies->delete('name');

        $this->assertFalse($this->cookies->has('name'));

        $this->cookies->set('name', 'mana');
        $this->assertTrue($this->cookies->has('name'));

        $this->cookies->set('!name', 'mana');
        $this->assertTrue($this->cookies->has('!name'));
    }

    public function test_delete()
    {
        $this->assertFalse($this->cookies->has('missing'));
        $this->cookies->delete('missing');

        $this->cookies->set('name', 'mana');
        $this->assertTrue($this->cookies->has('name'));
        $this->cookies->delete('name');
    }
}