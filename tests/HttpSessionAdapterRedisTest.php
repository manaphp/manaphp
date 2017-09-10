<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class HttpSessionAdapterRedisTest extends TestCase
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

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $adapter->write($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Redis();
        $adapter->setDependencyInjector($this->di);

        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp');
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