<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Adapter\Db;
use PHPUnit\Framework\TestCase;

class HttpSessionAdapterDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $di = new FactoryDefault();
        $di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);
            //   $db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

            $db->attachEvent('db:beforeQuery', function (DbInterface $source) {
                var_dump($source->getSQL());
                var_dump($source->getEmulatedSQL());
            });

            echo get_class($db), PHP_EOL;
            return $db;
        });
    }

    public function test_open()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db(['ttl' => 3600]);

        $this->assertTrue($adapter->open('', $session_id));
    }

    public function test_close()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new Db(['ttl' => 3600]);

        $this->assertTrue($adapter->close());
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();

        $adapter->open($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();

        $adapter->write($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();
        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
        $this->assertTrue($adapter->destroy($session_id));

        $this->assertEquals('', $adapter->read($session_id));
    }

    public function test_gc()
    {
        md5(microtime(true) . mt_rand());
        $adapter = new Db();
        $this->assertTrue($adapter->gc(100));
    }
}