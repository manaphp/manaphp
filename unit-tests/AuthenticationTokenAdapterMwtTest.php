<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class UserToken extends ManaPHP\Authentication\Token\Adapter\Mwt
{
    public $id;
    public $name;

    public function __construct()
    {
        parent::__construct(1, 'key');
    }
}

class  AuthenticationTokenAdapterMwtTest extends TestCase
{
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
        $userToken = new UserToken();
        $userToken->id = 100;
        $userToken->name = 'mana';

        $encoded = $userToken->encode(1);
        $this->assertContains('.', $encoded);
        $decodeUserToken = new UserToken();
        sleep(2);

        try {
            $decodeUserToken->decode($encoded);
            $this->fail('why not?');
        } catch (\Exception $e) {

        }

        for ($i = 0; $i < 10000; $i++) {
            $decodeUserToken->decode($userToken->encode());
        }
    }
}