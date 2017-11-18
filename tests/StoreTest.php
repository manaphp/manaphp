<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Store;
use ManaPHP\Store\Engine\File;
use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new FactoryDefault();
        $this->_di->alias->set('@data', sys_get_temp_dir());
    }

    public function test_exists()
    {
        $store = new Store(new File());

        $store->delete('country');
        $this->assertFalse($store->exists('country'));

        $store->set('country', 'china');
        $this->assertTrue($store->exists('country'));
    }

    public function test_get()
    {
        $store = new Store(new File());

        $store->delete('country');
        $this->assertFalse($store->get('country'));

        $store->set('country', 'china');
        $this->assertEquals('china', $store->get('country'));
    }

    public function test_set()
    {
        $store = new Store(new File());

        $store->delete('var');
        $store->delete('val');

        $this->assertFalse($store->get('var'));

        //null
        $store->set('var', null);
        $this->assertNull($store->get('var'));

        // false not support
        try {
            $store->set('var', false);
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertEquals('`var` key store value can not `false` boolean value', $e->getMessage());
        }

        // true
        $store->set('var', true);
        $this->assertTrue($store->get('var'));

        // int
        $store->set('var', 199);
        $this->assertSame(199, $store->get('var'));

        //float
        $store->set('var',1.5);
        $this->assertSame(1.5, $store->get('var'));

        //string
        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));

        $store->set('var','');
        $this->assertSame('',$store->get('var'));

        $store->set('var','{');
        $this->assertSame('{',$store->get('var'));

        $store->set('var','[');
        $this->assertSame('[',$store->get('var'));

        //array
        $store->set('var', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $store->get('var'));

        $store->set('var', ['_wrapper_' => 123]);
        $this->assertSame(['_wrapper_' => 123], $store->get('var'));

        $value = new \stdClass();
        $value->a = 123;
        $value->b = 'bbbb';

        $store->set('val', $value);
        $this->assertEquals((array)$value, $store->get('val'));
    }

    public function test_delete()
    {
        $store = new Store(new File());

        $store->delete('val');

        // delete a not existed
        $this->assertFalse($store->exists('val'));
        $store->delete('val');

        // delete an existed
        $store->set('country', 'china');
        $this->assertTrue($store->exists('country'));
        $store->delete('country');
        $this->assertFalse($store->exists('country'));
    }
}