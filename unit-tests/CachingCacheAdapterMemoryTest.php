<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CachingCacheAdapterMemoryTest extends TestCase
{
    public function test_exists()
    {
        $cache = new \ManaPHP\Caching\Cache\Adapter\Memory();
        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value', 1000);
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Caching\Cache\Adapter\Memory();
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Caching\Cache\Adapter\Memory();

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
        $cache = new \ManaPHP\Caching\Cache\Adapter\Memory();

        //exists and delete
        $cache->set('var', 'value', 100);
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}