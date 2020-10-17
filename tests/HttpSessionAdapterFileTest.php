<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Adapter\File;
use PHPUnit\Framework\TestCase;

class HttpSessionAdapterFileTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $di = new FactoryDefault();
        $di->alias->set('@data', sys_get_temp_dir());
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();

        $this->assertEquals('', $adapter->do_read($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();

        $adapter->do_write($session_id, '', 100);
        $this->assertEquals('', $adapter->do_read($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new File();
        $this->assertTrue($adapter->do_destroy($session_id));

        $adapter->do_write($session_id, 'manaphp', 100);
        $this->assertEquals('manaphp', $adapter->do_read($session_id));
        $this->assertTrue($adapter->do_destroy($session_id));

        $this->assertEquals('', $adapter->do_read($session_id));
    }

    public function test_gc()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new File();
        $this->assertTrue($adapter->do_gc(100));
    }
}