<?php

namespace Tests;

use ManaPHP\Counter\Engine\Redis;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CounterAdapterRedisTest extends TestCase
{
    public $di;

    public function test_construct()
    {
        $di = new FactoryDefault();

        //default
        $counter = new Redis();
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $counter);
        $this->assertAttributeSame('counter:', '_prefix', $counter);

        //string redis
        $counter = new Redis('abc');
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame('abc', '_redis', $counter);
        $this->assertAttributeSame('counter:', '_prefix', $counter);

        //array redis
        $counter = new Redis(['redis' => 'xxx']);
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame('xxx', '_redis', $counter);
        $this->assertAttributeSame('counter:', '_prefix', $counter);

        //array prefix
        $counter = new Redis(['prefix' => 'ppp:']);
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $counter);
        $this->assertAttributeSame('ppp:', '_prefix', $counter);

        //array redis and prefix
        $counter = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame('xx', '_redis', $counter);
        $this->assertAttributeSame('yy:', '_prefix', $counter);

        //object redis
        $redis = new \ManaPHP\Redis();
        $counter = new Redis($redis);
        $counter->setDependencyInjector($di);
        $this->assertAttributeSame($redis, '_redis', $counter);
        $this->assertAttributeSame('counter:', '_prefix', $counter);
    }

    public function test_get()
    {
        $di = new FactoryDefault();

        $counter = new Redis();
        $counter->setDependencyInjector($di);

        $counter->delete('c');

        $this->assertEquals(0, $counter->get('c'));
        $counter->increment('c', '1');
        $this->assertEquals(1, $counter->get('c'));
    }

    public function test_increment()
    {
        $di = new FactoryDefault();

        $counter = new Redis();
        $counter->setDependencyInjector($di);

        $counter->delete('c');
        $this->assertEquals(2, $counter->increment('c', 2));
        $this->assertEquals(22, $counter->increment('c', 20));
        $this->assertEquals(2, $counter->increment('c', -20));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();

        $counter = new Redis();
        $counter->setDependencyInjector($di);

        $counter->delete('c');

        $counter->increment('c', 1);
        $counter->delete('c');
    }
}