<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CachingStoreAdapterRedisTest extends TestCase
{
    public function test_exists()
    {
        /**
         * @var \ManaPHP\Caching\Store\Adapter\Redis\ConstructOptionsStub $options
         */
        $options = [];

        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);

        $cache->delete('var');

        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value');
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);

        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_mGet()
    {
        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);

        $cache->delete('1');
        $cache->delete('2');

        $cache->set('1', '1');

        $idValues = $cache->mGet(['1', '2']);
        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);

        $cache->set('var', '');
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '{}');
        $this->assertSame('{}', $cache->get('var'));
    }

    public function test_mSet()
    {
        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);
        $cache->delete(1);
        $cache->delete(2);
        $cache->delete(3);

        $cache->mSet([]);

        $cache->mSet(['1' => '1', '2' => '2']);
        $this->assertSame('1', $cache->get(1));
        $this->assertSame('2', $cache->get(2));
        $this->assertFalse($cache->get(3));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Caching\Store\Adapter\Redis([]);

        //exists and delete
        $cache->set('var', 'value');
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}