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
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        $store->delete('var');

        $this->assertFalse($store->exists('var'));
        $store->set('var', 'value');
        $this->assertTrue($store->exists('var'));
    }

    public function test_get()
    {
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        $store->delete('var');

        $this->assertFalse($store->get('var'));
        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));
    }

    public function test_mGet()
    {
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        $store->delete('1');
        $store->delete(2);

        $store->set('1', '1');
        $idValues = $store->mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_set()
    {
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        $store->set('var', '');
        $this->assertSame('', $store->get('var'));

        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));

        $store->set('var', '{}');
        $this->assertSame('{}', $store->get('var'));
    }

    public function test_mSet()
    {
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        $store->delete(1);
        $store->delete('2');

        $store->set('1', '1');
        $idValues = $store->mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_delete()
    {
        $store = new \ManaPHP\Store\Engine\File('/d/store/test');

        //exists and delete
        $store->set('var', 'value');
        $store->delete('var');

        // missing and delete
        $store->delete('var');
    }
}