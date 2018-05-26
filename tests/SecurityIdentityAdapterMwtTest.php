<?php
namespace Tests;

use ManaPHP\Security\Identity\Adapter\Mwt;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class  SecurityIdentityAdapterMwtTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->crypt->setMasterKey('mwt_key');
    }

    public function test_encode()
    {
        $mwt = new Mwt();
        $decoded = $mwt->decode($mwt->encode(['id' => 100, 'name' => 'mana']));
        $this->assertEquals(100, $decoded['id']);
        $this->assertEquals('mana', $decoded['name']);
    }

    public function test_decode()
    {
        $mwt = new Mwt();
        $decoded = $mwt->decode($mwt->encode(['id' => 100, 'name' => 'mana']));
        $this->assertEquals(100, $decoded['id']);
        $this->assertEquals('mana', $decoded['name']);
    }

    public function test_expire()
    {
        $mwt = new Mwt();
        $encoded = $mwt->encode(['id' => 100, 'name' => 'mana', 'exp' => 1]);
        sleep(2);
        $this->assertFalse($mwt->decode($encoded));
    }
}