<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Engine\Redis;
use PHPUnit\Framework\TestCase;

class HttpSessionEngineRedisTest extends TestCase
{
    public function test_construct()
    {
        $di = new FactoryDefault();

        //default
        $session = new Redis();
        $session->setDi($di);
        $this->assertAttributeSame('redis', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //string redis
        $session = new Redis('abc');
        $session->setDi($di);
        $this->assertAttributeSame('abc', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //array redis
        $session = new Redis(['redis' => 'xxx']);
        $session->setDi($di);
        $this->assertAttributeSame('xxx', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //array prefix
        $session = new Redis(['prefix' => 'ppp:']);
        $session->setDi($di);
        $this->assertAttributeSame('redis', '_redis', $session);
        $this->assertAttributeSame('ppp:', '_prefix', $session);

        //array redis and prefix
        $session = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $session->setDi($di);
        $this->assertAttributeSame('xx', '_redis', $session);
        $this->assertAttributeSame('yy:', '_prefix', $session);

        //object redis
        $redis = new \ManaPHP\Redis();
        $session = new Redis($redis);
        $session->setDi($di);
        $this->assertAttributeSame($redis, '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);
    }

    public function test_read()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setDi($di);

        $this->assertEquals('', $redis->read($session_id));

        $redis->write($session_id, 'manaphp', ['ttl' => 100]);
        $this->assertEquals('manaphp', $redis->read($session_id));
    }

    public function test_write()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setDi($di);

        $redis->write($session_id, '', ['ttl' => 100]);
        $this->assertEquals('', $redis->read($session_id));

        $redis->write($session_id, 'manaphp', ['ttl' => 100]);
        $this->assertEquals('manaphp', $redis->read($session_id));
    }

    public function test_destory()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setDi($di);

        $this->assertTrue($redis->destroy($session_id));

        $redis->write($session_id, 'manaphp', ['ttl' => 100]);
        $this->assertEquals('manaphp', $redis->read($session_id));
        $this->assertTrue($redis->destroy($session_id));

        $this->assertEquals('', $redis->read($session_id));
    }

    public function test_gc()
    {
        $di = new FactoryDefault();

        md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setDi($di);

        $this->assertTrue($redis->gc(100));
    }
}