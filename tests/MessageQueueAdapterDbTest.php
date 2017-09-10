<?php

namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Message\Queue\Adapter\Db;
use PHPUnit\Framework\TestCase;

class MessageQueueAdapterDbTest extends TestCase
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

    public function test_push()
    {
        $messageQueue = new Db();
        $messageQueue->delete('test');
        $messageQueue->push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->pop('test'));
    }

    public function test_pop()
    {
        $messageQueue = new Db();
        $messageQueue->delete('test');

        $this->assertFalse($messageQueue->pop('test', 0));

        $this->assertFalse($messageQueue->pop('test', 1));

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->pop('test'));
        $this->assertFalse($messageQueue->pop('test', 0));
        $this->assertFalse($messageQueue->pop('test', 1));
    }

    public function test_delete()
    {
        $messageQueue = new Db();

        $this->assertEquals(0, $messageQueue->length('test'));
        $messageQueue->delete('test');

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
        $messageQueue->delete('test');
        $this->assertEquals(0, $messageQueue->length('test'));
    }

    public function test_length()
    {
        $messageQueue = new Db();

        $messageQueue->delete('test');

        $this->assertEquals(0, $messageQueue->length('test'));

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
    }
}