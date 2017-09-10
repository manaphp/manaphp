<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MessageQueueAdapterDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new ManaPHP\Di\FactoryDefault();
        $di->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });

        $config = require __DIR__ . '/config.database.php';
        $db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
        // $this->db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);
        $db->attachEvent('db:beforeQuery', function (\ManaPHP\DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        });
        $di->setShared('db', $db);
    }

    public function test_push()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Db();
        $messageQueue->delete('test');
        $messageQueue->push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->pop('test'));
    }

    public function test_pop()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Db();
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
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Db();

        $this->assertEquals(0, $messageQueue->length('test'));
        $messageQueue->delete('test');

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
        $messageQueue->delete('test');
        $this->assertEquals(0, $messageQueue->length('test'));
    }

    public function test_length()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Db();

        $messageQueue->delete('test');

        $this->assertEquals(0, $messageQueue->length('test'));

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
    }
}