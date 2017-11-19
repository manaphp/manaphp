<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Engine\File;
use PHPUnit\Framework\TestCase;

class HttpSessionEngineFileTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $di = new FactoryDefault();
        $di->alias->set('@data', sys_get_temp_dir());
    }

    public function test_open()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();

        $this->assertTrue($adapter->open('', $session_id));
    }

    public function test_close()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new File();

        $this->assertTrue($adapter->close());
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();

        $adapter->open($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();

        $adapter->write($session_id, '', 100);
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();
        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->read($session_id));
        $this->assertTrue($adapter->destroy($session_id));

        $this->assertEquals('', $adapter->read($session_id));
    }

    public function test_gc()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new File();
        $this->assertTrue($adapter->gc(100));
    }
}