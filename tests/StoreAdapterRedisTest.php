<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Store\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class StoreAdapterRedisTest extends TestCase
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

    public function test_exists()
    {
        $store = new Redis();
        $store->setDependencyInjector($this->di);

        $store->delete('var');

        $this->assertFalse($store->exists('var'));
        $store->set('var', 'value');
        $this->assertTrue($store->exists('var'));
    }

    public function test_get()
    {
        $store = new Redis();
        $store->setDependencyInjector($this->di);

        $store->delete('var');

        $this->assertFalse($store->get('var'));
        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));
    }

    public function test_set()
    {
        $store = new Redis();
        $store->setDependencyInjector($this->di);

        $store->set('var', '');
        $this->assertSame('', $store->get('var'));

        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));

        $store->set('var', '{}');
        $this->assertSame('{}', $store->get('var'));
    }

    public function test_delete()
    {
        $store = new Redis();
        $store->setDependencyInjector($this->di);

        //exists and delete
        $store->set('var', 'value');
        $store->delete('var');

        // missing and delete
        $store->delete('var');
    }
}