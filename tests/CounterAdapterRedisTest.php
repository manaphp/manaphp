<?php

namespace Tests;

use ManaPHP\Counter\Engine\Redis;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CounterAdapterRedisTest extends TestCase
{
    public $di;

    public function setUp()
    {
        parent::setUp();

        $this->di = new FactoryDefault();
        $this->di->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });
    }

    public function test_get()
    {
        $counter = new Redis();
        $counter->setDependencyInjector($this->di);

        $counter->delete('c');

        $this->assertEquals(0, $counter->get('c'));
        $counter->increment('c', '1');
        $this->assertEquals(1, $counter->get('c'));
    }

    public function test_increment()
    {
        $counter = new Redis();
        $counter->setDependencyInjector($this->di);

        $counter->delete('c');
        $this->assertEquals(2, $counter->increment('c', 2));
        $this->assertEquals(22, $counter->increment('c', 20));
        $this->assertEquals(2, $counter->increment('c', -20));
    }

    public function test_delete()
    {
        $counter = new Redis();
        $counter->setDependencyInjector($this->di);

        $counter->delete('c');

        $counter->increment('c', 1);
        $counter->delete('c');
    }
}