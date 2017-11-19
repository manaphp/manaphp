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
        $session->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //string redis
        $session = new Redis('abc');
        $session->setDependencyInjector($di);
        $this->assertAttributeSame('abc', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //array redis
        $session = new Redis(['redis' => 'xxx']);
        $session->setDependencyInjector($di);
        $this->assertAttributeSame('xxx', '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);

        //array prefix
        $session = new Redis(['prefix' => 'ppp:']);
        $session->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $session);
        $this->assertAttributeSame('ppp:', '_prefix', $session);

        //array redis and prefix
        $session = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $session->setDependencyInjector($di);
        $this->assertAttributeSame('xx', '_redis', $session);
        $this->assertAttributeSame('yy:', '_prefix', $session);

        //object redis
        $redis = new \ManaPHP\Redis();
        $session = new Redis($redis);
        $session->setDependencyInjector($di);
        $this->assertAttributeSame($redis, '_redis', $session);
        $this->assertAttributeSame('session:', '_prefix', $session);
    }

    public function test_open()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $this->assertTrue($adapter->open('', $session_id));
    }

    public function test_close()
    {
        $di = new FactoryDefault();

        md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $this->assertTrue($adapter->close());
    }

    public function test_read()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $adapter->open($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $adapter->write($session_id, '', 100);
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $di = new FactoryDefault();

        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
        $this->assertTrue($adapter->destroy($session_id));

        $this->assertEquals('', $adapter->read($session_id));
    }

    public function test_gc()
    {
        $di = new FactoryDefault();

        md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($di);

        $this->assertTrue($adapter->gc(100));
    }
}