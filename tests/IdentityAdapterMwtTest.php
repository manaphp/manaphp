<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Identity\Adapter\Mwt;
use PHPUnit\Framework\TestCase;

class  IdentityAdapterMwtTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->crypt->setMasterKey('mwt_key');
        $di->logger->removeAppender('file');
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