<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Engine\Redis;
use PHPUnit\Framework\TestCase;

class HttpSessionEngineRedisTest extends TestCase
{
    public $di;

    public function setUp()
    {
        parent::setUp();

        $this->di = new FactoryDefault();
        $this->di->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });
    }

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
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $this->assertTrue($adapter->open('', $session_id));
    }

    public function test_close()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $this->assertTrue($adapter->close());
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $adapter->open($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $adapter->write($session_id, '', 100);
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
        $this->assertTrue($adapter->destroy($session_id));

        $this->assertEquals('', $adapter->read($session_id));
    }

    public function test_gc()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $this->assertTrue($adapter->gc(100));
    }
}