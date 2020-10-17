<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Security\Crypt;
use PHPUnit\Framework\TestCase;

class SecurityCryptTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        new FactoryDefault();
    }

    public function test_encrypt()
    {
        $crypt = new Crypt();

        $data = '1234';
        $key = 'abc';
        $this->assertEquals($data, $crypt->decrypt($crypt->encrypt($data, $key), $key));
    }
}