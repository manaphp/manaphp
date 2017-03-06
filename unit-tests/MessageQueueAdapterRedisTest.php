<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MessageQueueAdapterRedisTest extends TestCase
{
    public $di;

    public function setUp()
    {
        parent::setUp();
        $this->di = new ManaPHP\Di\FactoryDefault();
        $this->di->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });
    }

    public function test_push()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Redis();
        $messageQueue->setDependencyInjector($this->di);
        $messageQueue->delete('test');
        $messageQueue->push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->pop('test'));
    }

    public function test_pop()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Redis();
        $messageQueue->setDependencyInjector($this->di);

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
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Redis();
        $messageQueue->setDependencyInjector($this->di);

        $this->assertEquals(0, $messageQueue->length('test'));
        $messageQueue->delete('test');

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
        $messageQueue->delete('test');
        $this->assertEquals(0, $messageQueue->length('test'));
    }

    public function test_length()
    {
        $messageQueue = new \ManaPHP\Message\Queue\Adapter\Redis();
        $messageQueue->setDependencyInjector($this->di);

        $messageQueue->delete('test');

        $this->assertEquals(0, $messageQueue->length('test'));

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
    }
}