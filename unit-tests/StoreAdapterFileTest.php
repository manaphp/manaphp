<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class StoreAdapterFileTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $di = new ManaPHP\Di\FactoryDefault();
    }

    public function test_exists()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $cache->_delete('var');

        $this->assertFalse($cache->_exists('var'));
        $cache->_set('var', 'value');
        $this->assertTrue($cache->_exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $cache->_delete('var');

        $this->assertFalse($cache->_get('var'));
        $cache->_set('var', 'value');
        $this->assertSame('value', $cache->_get('var'));
    }

    public function test_mGet()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $cache->_delete('1');
        $cache->_delete(2);

        $cache->_set('1', '1');
        $idValues = $cache->_mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $cache->_set('var', '');
        $this->assertSame('', $cache->_get('var'));

        $cache->_set('var', 'value');
        $this->assertSame('value', $cache->_get('var'));

        $cache->_set('var', '{}');
        $this->assertSame('{}', $cache->_get('var'));
    }

    public function test_mSet()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $cache->_delete(1);
        $cache->_delete('2');

        $cache->_set('1', '1');
        $idValues = $cache->_mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Store\Adapter\File('/d/store/test');

        //exists and delete
        $cache->_set('var', 'value');
        $cache->_delete('var');

        // missing and delete
        $cache->_delete('var');
    }
}