<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CacheAdapterApcTest extends TestCase
{
    public function test_exists()
    {
        $cache = new \ManaPHP\Cache\Adapter\Apc();
        $cache->_delete('var');
        $this->assertFalse($cache->_exists('var'));
        $cache->_set('var', 'value', 1000);
        $this->assertTrue($cache->_exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Cache\Adapter\Apc();
        $cache->_delete('var');

        $this->assertFalse($cache->_get('var'));
        $cache->_set('var', 'value', 100);
        $this->assertSame('value', $cache->_get('var'));
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Cache\Adapter\Apc();

        $cache->_set('var', '', 100);
        $this->assertSame('', $cache->_get('var'));

        $cache->_set('var', 'value', 100);
        $this->assertSame('value', $cache->_get('var'));

        $cache->_set('var', '{}', 100);
        $this->assertSame('{}', $cache->_get('var'));

        // ttl
        $cache->_set('var', 'value', 1);
        $this->assertTrue($cache->_exists('var'));
        sleep(2);
        /**
         * After the ttl has passed, the stored variable will be expunged from the cache (on the next request). If no ttl is supplied (or if the ttl is 0), the value will persist until it is removed from the cache manually, or otherwise fails to exist in the cache (clear, restart, etc.).
         */
        $this->assertTrue($cache->_exists('var'));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Cache\Adapter\Apc();

        //exists and delete
        $cache->_set('var', 'value', 100);
        $cache->_delete('var');

        // missing and delete
        $cache->_delete('var');
    }
}