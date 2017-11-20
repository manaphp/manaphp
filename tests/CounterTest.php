<?php

namespace Tests;

use ManaPHP\Counter;
use ManaPHP\Counter\Engine\Redis;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CounterTest extends TestCase
{
    public function test_construct()
    {
        //default
        $di = new FactoryDefault();

        $counter = new Counter();
        $counter->setDependencyInjector($di);

        $this->assertAttributeSame(Redis::class, '_engine', $counter);
        $counter->get('xxx');
        $this->assertAttributeInstanceOf(Redis::class, '_engine', $counter);
        $this->assertAttributeSame('', '_prefix', $counter);

        //instance
        $di = new FactoryDefault();

        $redis = new Redis();
        $counter = new Counter($redis);
        $this->assertAttributeSame($redis, '_engine', $counter);
        $this->assertAttributeSame('', '_prefix', $counter);

        //class name string
        $counter = new Counter(Redis::class);
        $counter->setDependencyInjector($di);

        $this->assertAttributeSame(Redis::class, '_engine', $counter);
        $counter->get('abc');
        $this->assertAttributeInstanceOf(Redis::class, '_engine', $counter);
        $this->assertAttributeSame('', '_prefix', $counter);

        //component name string
        $di->setShared('redisCounterEngine', Redis::class);
        $counter = new Counter('redisCounterEngine');
        $counter->setDependencyInjector($di);

        $this->assertAttributeSame('redisCounterEngine', '_engine', $counter);
        $counter->get('abc');
        $this->assertAttributeInstanceOf(Redis::class, '_engine', $counter);
        $this->assertAttributeSame('', '_prefix', $counter);

        //array
        $counter = new Counter(['engine' => Redis::class, 'prefix' => 'AAA']);
        $counter->setDependencyInjector($di);

        $this->assertAttributeSame(Redis::class, '_engine', $counter);
        $counter->get('abc');
        $this->assertAttributeInstanceOf(Redis::class, '_engine', $counter);
        $this->assertAttributeSame('AAA', '_prefix', $counter);

        //array
        $counter = new Counter(['engine' => ['class' => Redis::class, 'dir' => 'xxx']]);
        $counter->setDependencyInjector($di);

        $this->assertAttributeSame(['class' => Redis::class, 'dir' => 'xxx'], '_engine', $counter);
        $counter->get('abc');
        $this->assertAttributeInstanceOf(Redis::class, '_engine', $counter);

        $this->assertAttributeSame('', '_prefix', $counter);
    }

    public function test_get()
    {
        $di = new FactoryDefault();

        $counter = new Counter($di->getShared(Redis::class));

        $counter->delete('c');

        $this->assertEquals(0, $counter->get('c'));
        $counter->increment('c');
        $this->assertEquals(1, $counter->get('c'));
    }

    public function test_increment()
    {
        $di = new FactoryDefault();

        $counter = new Counter($di->getShared(Redis::class));

        $counter->delete('c');
        $this->assertEquals(1, $counter->increment('c'));
        $this->assertEquals(2, $counter->increment('c', 1));
        $this->assertEquals(22, $counter->increment('c', 20));
        $this->assertEquals(2, $counter->increment('c', -20));

        $counter->delete('c');
        $this->assertEquals(0, $counter->get('c'));
    }

    public function test_decrement()
    {
        $di = new FactoryDefault();

        $counter = new Counter($di->getShared(Redis::class));

        $counter->delete('c');
        $this->assertEquals(-1, $counter->decrement('c'));
        $this->assertEquals(-2, $counter->decrement('c', 1));
        $this->assertEquals(-22, $counter->decrement('c', 20));
        $this->assertEquals(-2, $counter->decrement('c', -20));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();

        $counter = new Counter(Redis::class);
        $counter->setDependencyInjector($di);
        $counter->delete('c');

        $counter->increment('c');
        $counter->delete('c');
    }
}