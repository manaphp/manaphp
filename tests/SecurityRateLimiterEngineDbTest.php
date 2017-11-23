<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Security\RateLimiter\Engine\Db;
use PHPUnit\Framework\TestCase;

class SecurityRateLimiterEngineDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });

        $config = require __DIR__ . '/config.database.php';
        $db = new Mysql($config['mysql']);
        // $this->db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);
        $db->attachEvent('db:beforeQuery', function (\ManaPHP\DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        });
        $di->setShared('db', $db);
    }

    public function test_check()
    {
        $db = new Db();
        $this->assertEquals(1, $db->check('test', 1, 2));
        $this->assertEquals(2, $db->check('test', 1, 2));
        sleep(3);
        $this->assertEquals(1, $db->check('test', 1, 2));
    }
}