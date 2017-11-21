<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Message\Queue\Engine\Redis;
use PHPUnit\Framework\TestCase;

class MessageQueueEngineRedisTest extends TestCase
{
    public function test_push()
    {
        $di = new FactoryDefault();
        $messageQueue = $di->getShared(Redis::class);

        $messageQueue->delete('test');
        $messageQueue->push('test', 'manaphp');
        $this->assertEquals('manaphp', $messageQueue->pop('test'));
    }

    public function test_pop()
    {

        $di = new FactoryDefault();
        $messageQueue = $di->getShared(Redis::class);

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
        $di = new FactoryDefault();
        $messageQueue = $di->getShared(Redis::class);

        $this->assertEquals(0, $messageQueue->length('test'));
        $messageQueue->delete('test');

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
        $messageQueue->delete('test');
        $this->assertEquals(0, $messageQueue->length('test'));
    }

    public function test_length()
    {
        $di = new FactoryDefault();
        $messageQueue = $di->getShared(Redis::class);

        $messageQueue->delete('test');

        $this->assertEquals(0, $messageQueue->length('test'));

        $messageQueue->push('test', 'manaphp');
        $this->assertEquals(1, $messageQueue->length('test'));
    }
}