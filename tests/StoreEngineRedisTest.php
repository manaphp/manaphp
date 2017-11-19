<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Store\Engine\Redis;
use PHPUnit\Framework\TestCase;

class StoreEngineRedisTest extends TestCase
{
    public function test_construct()
    {
        $di = new FactoryDefault();

        //default
        $store = new Redis();
        $store->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $store);
        $this->assertAttributeSame('store:', '_prefix', $store);

        //string redis
        $store = new Redis('abc');
        $store->setDependencyInjector($di);
        $this->assertAttributeSame('abc', '_redis', $store);
        $this->assertAttributeSame('store:', '_prefix', $store);

        //array redis
        $store = new Redis(['redis' => 'xxx']);
        $store->setDependencyInjector($di);
        $this->assertAttributeSame('xxx', '_redis', $store);
        $this->assertAttributeSame('store:', '_prefix', $store);

        //array prefix
        $store = new Redis(['prefix' => 'ppp:']);
        $store->setDependencyInjector($di);
        $this->assertAttributeSame('redis', '_redis', $store);
        $this->assertAttributeSame('ppp:', '_prefix', $store);

        //array redis and prefix
        $store = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $store->setDependencyInjector($di);
        $this->assertAttributeSame('xx', '_redis', $store);
        $this->assertAttributeSame('yy:', '_prefix', $store);

        //object redis
        $redis = new \ManaPHP\Redis();
        $store = new Redis($redis);
        $store->setDependencyInjector($di);
        $this->assertAttributeSame($redis, '_redis', $store);
        $this->assertAttributeSame('store:', '_prefix', $store);
    }

    public function test_exists()
    {
        $di = new FactoryDefault();

        $store = new Redis();
        $store->setDependencyInjector($di);

        $store->delete('var');

        $this->assertFalse($store->exists('var'));
        $store->set('var', 'value');
        $this->assertTrue($store->exists('var'));
    }

    public function test_get()
    {
        $di = new FactoryDefault();

        $store = new Redis();
        $store->setDependencyInjector($di);

        $store->delete('var');

        $this->assertFalse($store->get('var'));
        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));
    }

    public function test_set()
    {
        $di = new FactoryDefault();

        $store = new Redis();
        $store->setDependencyInjector($di);

        $store->set('var', '');
        $this->assertSame('', $store->get('var'));

        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));

        $store->set('var', '{}');
        $this->assertSame('{}', $store->get('var'));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();

        $store = new Redis();
        $store->setDependencyInjector($di);

        //exists and delete
        $store->set('var', 'value');
        $store->delete('var');

        // missing and delete
        $store->delete('var');
    }
}