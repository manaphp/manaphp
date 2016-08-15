<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class SecurityCryptTest extends TestCase
{
    public function test_encrypt()
    {
        $crypt = new \ManaPHP\Security\Crypt();

        $data = '1234';
        $key = 'abc';
        $this->assertEquals($data, $crypt->decrypt($crypt->encrypt($data, $key), $key));
    }
}