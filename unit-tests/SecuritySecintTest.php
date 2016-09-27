<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class SecuritySecintTest extends TestCase
{
    public function test_encode(){
        $secint=new \ManaPHP\Security\Secint();
        $this->assertEquals(-100,$secint->decode($secint->encode(-100)));
        $this->assertEquals(0,$secint->decode($secint->encode(0)));
        $this->assertEquals(100,$secint->decode($secint->encode(100)));
        $this->assertEquals(11, strlen($secint->encode(10)));
        //$this->assertEquals('D4HXu2jY1Qw',$hashIds->encode([1,2,10234657]));
    }

    public function test_decode(){
        $secint=new \ManaPHP\Security\Secint();
        $this->assertEquals(-100,$secint->decode($secint->encode(-100)));
        $this->assertEquals(false,$secint->decode('1223'));
        $this->assertEquals(868, $secint->decode('uGlzQRVuqaA'));
    }
}