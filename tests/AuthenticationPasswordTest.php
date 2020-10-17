<?php

namespace Tests;

use ManaPHP\Password;
use PHPUnit\Framework\TestCase;

class AuthenticationPasswordTest extends TestCase
{
    public function test_salt()
    {
        $password = new Password();

        $this->assertEquals(1, strlen($password->salt(1)));
        $this->assertEquals(8, strlen($password->salt(8)));
        $this->assertEquals(16, strlen($password->salt(16)));
        $this->assertEquals(24, strlen($password->salt(24)));
        $this->assertEquals(32, strlen($password->salt(32)));
        $this->assertEquals(40, strlen($password->salt(40)));//max length
    }

    public function test_hash()
    {
        $password = new Password();

        $this->assertEquals('90d84517265740fa4009217c70df1576', $password->hash('manaphp'));
        $this->assertEquals('fb59be6e62639c9804198b2873695a67', $password->hash('manaphp', '12345678'));
    }

    public function test_verify()
    {
        $password = new Password();

        $this->assertTrue($password->verify('manaphp', '90d84517265740fa4009217c70df1576'));
        $this->assertFalse($password->verify('manaphp', '90d84517265740fa4009217c70df157D'));

        $this->assertTrue($password->verify('manaphp', 'fb59be6e62639c9804198b2873695a67', '12345678'));
        $this->assertFalse($password->verify('manaphp', 'fb59be6e62639c9804198b2873695a6D'));
    }
}