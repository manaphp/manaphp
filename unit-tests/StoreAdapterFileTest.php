<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class StoreAdapterFileTest extends TestCase
{
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new ManaPHP\Di\FactoryDefault();
    }

    public function test_exists()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $store->_delete('var');

        $this->assertFalse($store->_exists('var'));
        $store->_set('var', 'value');
        $this->assertTrue($store->_exists('var'));
    }

    public function test_get()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $store->_delete('var');

        $this->assertFalse($store->_get('var'));
        $store->_set('var', 'value');
        $this->assertSame('value', $store->_get('var'));
    }

    public function test_mGet()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $store->_delete('1');
        $store->_delete(2);

        $store->_set('1', '1');
        $idValues = $store->_mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_set()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $store->_set('var', '');
        $this->assertSame('', $store->_get('var'));

        $store->_set('var', 'value');
        $this->assertSame('value', $store->_get('var'));

        $store->_set('var', '{}');
        $this->assertSame('{}', $store->_get('var'));
    }

    public function test_mSet()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        $store->_delete(1);
        $store->_delete('2');

        $store->_set('1', '1');
        $idValues = $store->_mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_delete()
    {
        $store = new \ManaPHP\Store\Adapter\File('/d/store/test');

        //exists and delete
        $store->_set('var', 'value');
        $store->_delete('var');

        // missing and delete
        $store->_delete('var');
    }
}