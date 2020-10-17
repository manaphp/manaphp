<?php

namespace Tests;

use ManaPHP\Helper\Ip;
use PHPUnit\Framework\TestCase;

class HelpIpTest extends TestCase
{
    public function test_contains()
    {
        $this->assertTrue(Ip::contains('192.168.0.10', '192.168.0.10'));
        $this->assertFalse(Ip::contains('192.168.0.11', '192.168.0.10'));

        $this->assertTrue(Ip::contains('*', '192.168.0.10'));
        $this->assertTrue(Ip::contains('192.168.0.*', '192.168.0.10'));
        $this->assertFalse(Ip::contains('192.168.1.*', '192.168.0.10'));
        $this->assertTrue(Ip::contains('192.168.*.*', '192.168.0.10'));

        $this->assertTrue(Ip::contains('192.168.0.5-192.168.10.15', '192.168.0.10'));
        $this->assertTrue(Ip::contains('192.168.0.10-192.168.10.15', '192.168.0.10'));
        $this->assertTrue(Ip::contains('192.168.0.5-192.168.10.10', '192.168.0.10'));
        $this->assertFalse(Ip::contains('192.168.0.5-192.168.0.7', '192.168.0.10'));

        $this->assertTrue(Ip::contains('192.168.0.0/24', '192.168.0.10'));
        $this->assertTrue(Ip::contains('0.0.0.0/0', '192.168.0.10'));
        $this->assertFalse(Ip::contains('192.168.1.0/24', '192.168.0.10'));

        $this->assertTrue(Ip::contains('192.168.0.0/255.255.255.0', '192.168.0.10'));
        $this->assertFalse(Ip::contains('192.168.1.0/255.255.255.0', '192.168.0.10'));
    }
}
