<?php

namespace Tests;

use ManaPHP\Security\Random;
use PHPUnit\Framework\TestCase;

class SecurityRandomTest extends TestCase
{
    public function test_getByte()
    {
        $random = new Random();
        $this->assertEquals(0, strlen($random->getByte(0)));
        $this->assertEquals(1, strlen($random->getByte(1)));
        $this->assertEquals(1024, strlen($random->getByte(1024)));
    }

    public function test_getBase62()
    {
        $random = new Random();
        $this->assertEquals(0, strlen($random->getBase(0)));
        $this->assertEquals(1, strlen($random->getBase(1)));
        $this->assertEquals(1024, strlen($random->getBase(1024)));
    }

    public function test_getInt()
    {
        $random = new Random();
        $this->assertEquals(1, $random->getInt(1, 1));
    }

    public function test_getFloat()
    {
        $random = new Random();
        $this->assertEquals(0.1, $random->getFloat(0.1, 0.1));
    }

}
