<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Session\Engine\Db;
use PHPUnit\Framework\TestCase;

class HttpSessionEngineDbTest extends TestCase
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

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();

        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', ['ttl' => 100, 'user_id' => 0, 'client_ip' => '0.0.0.0']);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();

        $adapter->write($session_id, '', ['ttl' => 100, 'user_id' => 0, 'client_ip' => '0.0.0.0']);
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp', ['ttl' => 100, 'user_id' => 0, 'client_ip' => '0.0.0.0']);
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new Db();
        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp', ['ttl' => 100, 'user_id' => 0, 'client_ip' => '0.0.0.0']);
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