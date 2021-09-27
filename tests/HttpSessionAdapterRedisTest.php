<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class HttpSessionAdapterRedisTest extends TestCase
{
    public function test_construct()
    {
        $di = new FactoryDefault();

        //default
        $session = new Redis();
        $session->setInjector($di);
        $this->assertAttributeSame('redis', 'redis', $session);
        $this->assertAttributeSame('session:', 'prefix', $session);

        //array redis
        $session = new Redis(['redis' => 'xxx']);
        $session->setInjector($di);
        $this->assertAttributeSame('xxx', 'redis', $session);
        $this->assertAttributeSame('session:', 'prefix', $session);

        //array prefix
        $session = new Redis(['prefix' => 'ppp:']);
        $session->setInjector($di);
        $this->assertAttributeSame('redis', 'redis', $session);
        $this->assertAttributeSame('ppp:', 'prefix', $session);

        //array redis and prefix
        $session = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $session->setInjector($di);
        $this->assertAttributeSame('xx', 'redis', $session);
        $this->assertAttributeSame('yy:', 'prefix', $session);
    }

    public function test_read()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setInjector($di);

        $this->assertEquals('', $redis->do_read($session_id));

        $redis->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $redis->do_read($session_id));
    }

    public function test_write()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setInjector($di);

        $redis->do_write($session_id, '', 100);
        $this->assertEquals('', $redis->do_read($session_id));

        $redis->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $redis->do_read($session_id));
    }

    public function test_destory()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setInjector($di);

        $this->assertTrue($redis->do_destroy($session_id));

        $redis->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $redis->do_read($session_id));
        $this->assertTrue($redis->do_destroy($session_id));

        $this->assertEquals('', $redis->do_read($session_id));
    }

    public function test_gc()
    {
        $di = new FactoryDefault();

        md5(microtime(true) . mt_rand());
        $redis = new Redis();
        $redis->setInjector($di);

        $this->assertTrue($redis->do_gc(100));
    }
}