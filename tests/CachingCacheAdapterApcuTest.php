<?php

namespace Tests;

use ManaPHP\Caching\Cache\Adapter\Apcu;
use PHPUnit\Framework\TestCase;

class CachingCacheAdapterApcuTest extends TestCase
{
    /**
     * @requires  extension apc
     */
    public function test_exists()
    {
        if (!function_exists('apcu_exists')) {
            $this->markTestSkipped();
            return;
        }

        $cache = new Apcu();
        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value', 1000);
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        if (!function_exists('apcu_exists')) {
            $this->markTestSkipped();
            return;
        }

        $cache = new Apcu();
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        if (!function_exists('apcu_exists')) {
            $this->markTestSkipped();
            return;
        }

        $cache = new Apcu();

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
        /**
         * After the ttl has passed, the stored variable will be expunged from the cache (on the next request). If no ttl is supplied (or if the ttl is 0), the value will persist until it is removed from the cache manually, or otherwise fails to exist in the cache (clear, restart, etc.).
         */
        $this->assertTrue($cache->exists('var'));
    }

    public function test_delete()
    {
        if (!function_exists('apcu_exists')) {
            $this->markTestSkipped();
            return;
        }

        $cache = new Apcu();

        //exists and delete
        $cache->set('var', 'value', 100);
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}