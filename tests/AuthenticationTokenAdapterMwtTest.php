<?php
namespace Tests;

use ManaPHP\Authentication\Token\Adapter\Mwt;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Exception;
use PHPUnit\Framework\TestCase;

class UserToken extends Mwt
{
    public $id;
    public $name;
}

class  AuthenticationTokenAdapterMwtTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->crypt->setMasterKey('mwt_key');
    }

    public function test_encode()
    {
        $userToken = new UserToken();
        $userToken->id = 100;
        $userToken->name = 'mana';

        $encoded = $userToken->encode();
        $this->assertContains('.', $encoded);
        $decodeUserToken = new UserToken();
        $decodeUserToken->decode($encoded);

        $this->assertEquals($userToken->id, $decodeUserToken->id);
        $this->assertEquals($userToken->name, $decodeUserToken->name);
    }

    public function test_decode()
    {
        $userToken = new UserToken();
        $userToken->id = 100;
        $userToken->name = 'mana';

        $encoded = $userToken->encode();
        $this->assertContains('.', $encoded);
        $decodeUserToken = new UserToken();
        $decodeUserToken->decode($encoded);

        $this->assertEquals($userToken->id, $decodeUserToken->id);
        $this->assertEquals($userToken->name, $decodeUserToken->name);

        //expire
        $userToken = new UserToken(['ttl' => 1]);
        $userToken->id = 100;
        $userToken->name = 'mana';

        $encoded = $userToken->encode();
        $this->assertContains('.', $encoded);
        $decodeUserToken = new UserToken();
        sleep(2);

        try {
            $decodeUserToken->decode($encoded);
            $this->fail('why not?');
        } catch (Exception $e) {

        }

        for ($i = 0; $i < 10000; $i++) {
            $decodeUserToken->decode($userToken->encode());
        }
    }
}