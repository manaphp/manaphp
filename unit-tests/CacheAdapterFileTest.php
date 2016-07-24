<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CacheAdapterFileTest extends TestCase
{
    protected $_di;
    public function setUp()
    {
        parent::setUp();

        $this->_di = new ManaPHP\Di\FactoryDefault();
    }

    public function test_exists()
    {
        $cache = new \ManaPHP\Cache\Adapter\File('/d/cache/test');

        $cache->_delete('var');
        $this->assertFalse($cache->_exists('var'));
        $cache->_set('var', 'value', 1000);
        $this->assertTrue($cache->_exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Cache\Adapter\File('/d/cache/test');

        $cache->_delete('var');

        $this->assertFalse($cache->_get('var'));
        $cache->_set('var', 'value', 100);
        $this->assertSame('value', $cache->_get('var'));
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Cache\Adapter\File('/d/cache/test');

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
        $this->assertFalse($cache->_exists('var'));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Cache\Adapter\File('/d/cache/test');

        //exists and delete
        $cache->_set('var', 'value', 100);
        $cache->_delete('var');

        // missing and delete
        $cache->_delete('var');
    }
}