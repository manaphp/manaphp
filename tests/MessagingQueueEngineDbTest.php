<?php

namespace Tests;

use ManaPHP\Data\Db\Adapter\Mysql;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Messaging\Queue\Adapter\Db;
use PHPUnit\Framework\TestCase;

class MessagingQueueEngineDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new FactoryDefault();
        $di->set(
            'redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        }
        );

        $config = require __DIR__ . '/config.database.php';
        $db = new Mysql($config['mysql']);
        // $this->db = new ManaPHP\Data\Db\Adapter\Sqlite($config['sqlite']);
        $db->attachEvent(
            'db:beforeQuery', function (\ManaPHP\Data\DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        }
        );
        $di->set('db', $db);
    }

    public function test_push()
    {
        $messageQueue = new Db();
        $messageQueue->setContainer(container());

        $messageQueue->do_delete('test');
        $messageQueue->do_push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->do_pop('test'));
    }

    public function test_pop()
    {
        $messageQueue = new Db();
        $messageQueue->setContainer(container());

        $messageQueue->do_delete('test');

        $this->assertFalse($messageQueue->do_pop('test', 0));

        $this->assertFalse($messageQueue->do_pop('test', 1));

        $messageQueue->do_push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->do_pop('test'));
        $this->assertFalse($messageQueue->do_pop('test', 0));
        $this->assertFalse($messageQueue->do_pop('test', 1));
    }

    public function test_delete()
    {
        $messageQueue = new Db();
        $messageQueue->setContainer(container());

        $this->assertEquals(0, $messageQueue->do_length('test'));
        $messageQueue->do_delete('test');

        $messageQueue->do_push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->do_length('test'));
        $messageQueue->do_delete('test');
        $this->assertEquals(0, $messageQueue->do_length('test'));
    }

    public function test_length()
    {
        $messageQueue = new Db();
        $messageQueue->setContainer(container());

        $messageQueue->do_delete('test');

        $this->assertEquals(0, $messageQueue->do_length('test'));

        $messageQueue->do_push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->do_length('test'));
    }
}