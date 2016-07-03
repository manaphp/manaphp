<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class StoreAdapterMemoryTest extends TestCase
{
    public function test_exists()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();
        $cache->_delete('var');
        $this->assertFalse($cache->_exists('var'));
        $cache->_set('var', 'value');
        $this->assertTrue($cache->_exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();
        $cache->_delete('var');

        $this->assertFalse($cache->_get('var'));
        $cache->_set('var', 'value');
        $this->assertSame('value', $cache->_get('var'));
    }

    public function test_mGet()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();

        $cache->_set('1', '1');
        $idValues = $cache->_mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();

        $cache->_set('var', '');
        $this->assertSame('', $cache->_get('var'));

        $cache->_set('var', 'value');
        $this->assertSame('value', $cache->_get('var'));

        $cache->_set('var', '{}');
        $this->assertSame('{}', $cache->_get('var'));
    }

    public function test_mSet()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();

        $cache->_mSet([]);

        $cache->_mSet(['1' => 1, '2' => 2]);
        $this->assertSame(1, $cache->_get(1));
        $this->assertSame(2, $cache->_get(2));
        $this->assertFalse($cache->_get(3));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Store\Adapter\Memory();

        //exists and delete
        $cache->_set('var', 'value');
        $cache->_delete('var');

        // missing and delete
        $cache->_delete('var');
    }
}