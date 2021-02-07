<?php

namespace Tests;

use ManaPHP\Caching\Cache\Adapter\Redis;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CachingCacheAdapterRedisTest extends TestCase
{
    public function test_construct()
    {
        $di = new FactoryDefault();

        //default
        $cache = new Redis();
        $cache->setContainer($di);
        $this->assertAttributeSame('redis', 'redis', $cache);
        $this->assertAttributeSame('cache:', 'prefix', $cache);

        //array redis
        $cache = new Redis(['redis' => 'xxx']);
        $cache->setContainer($di);
        $this->assertAttributeSame('xxx', 'redis', $cache);
        $this->assertAttributeSame('cache:', 'prefix', $cache);

        //array prefix
        $cache = new Redis(['prefix' => 'ppp:']);
        $cache->setContainer($di);
        $this->assertAttributeSame('redis', 'redis', $cache);
        $this->assertAttributeSame('ppp:', 'prefix', $cache);

        //array redis and prefix
        $cache = new Redis(['redis' => 'xx', 'prefix' => 'yy:']);
        $cache->setContainer($di);
        $this->assertAttributeSame('xx', 'redis', $cache);
        $this->assertAttributeSame('yy:', 'prefix', $cache);
    }

    public function test_exists()
    {
        $di = new FactoryDefault();

        $cache = new Redis();
        $cache->setContainer($di);

        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value', 1000);
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $di = new FactoryDefault();

        $cache = new Redis();
        $cache->setContainer($di);

        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        $di = new FactoryDefault();

        $cache = new Redis();
        $cache->setContainer($di);

        $cache->set('var', '', 100);
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '{}', 100);
        $this->assertSame('{}', $cache->get('var'));

        // ttl
        $cache->set('var', 'value', 1);
        $this->assertTrue($cache->exists('var'));
        sleep(2);
        $this->assertFalse($cache->exists('var'));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();

        $cache = new Redis();
        $cache->setContainer($di);

        //exists and delete
        $cache->set('var', 'value', 100);
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}