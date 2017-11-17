<?php
namespace Tests;

use ManaPHP\Cache;
use ManaPHP\Cache\Engine\File;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new FactoryDefault();
        $this->_di->alias->set('@data', sys_get_temp_dir());
    }

    public function test_exists()
    {
        $cache = new Cache(new File());
        $cache->delete('country');

        $this->assertFalse($cache->exists('country'));

        $cache->set('country', 'china', 100);
        $this->assertTrue($cache->exists('country'));
    }

    public function test_get()
    {
        $cache = new Cache(new File());

        $cache->delete('country');
        $this->assertFalse($cache->get('country'));

        $cache->set('country', 'china', 100);
        $this->assertEquals('china', $cache->get('country'));
    }

    public function test_set()
    {
        $cache = new Cache(new File());
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));

        // false
        $cache->set('var', false, 100);
        $this->assertEquals(false, $cache->get('var'));
        $this->assertSame(false, $cache->get('val'));
        // true
        $cache->set('var', true, 100);
        $this->assertSame(true, $cache->get('var'));

        // int
        $cache->set('var', 199, 100);
        $this->assertSame(199, $cache->get('var'));

        //string
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));

        //array
        $cache->set('var', [1, 2, 3], 100);
        $this->assertSame([1, 2, 3], $cache->get('var'));

        $value = new \stdClass();
        $value->a = 123;
        $value->b = 'bbbb';

        // object and save as object
        $cache->set('val', $value, 100);
        $this->assertEquals($value, $cache->get('val'));
        $this->assertInstanceOf('\stdClass', $cache->get('val'));

        // object and save as array
        $cache->set('val', (array)$value, 100);
        $this->assertEquals((array)$value, $cache->get('val'));
        $this->assertTrue(is_array($cache->get('val')));
    }

    public function test_delete()
    {
        $cache = new Cache(new File());
        $cache->delete('val');

        // delete a not existed
        $this->assertFalse($cache->exists('val'));
        $cache->delete('val');

        // delete an existed
        $cache->set('country', 'china', 100);
        $this->assertTrue($cache->exists('country'));
        $cache->delete('country');
        $this->assertFalse($cache->exists('country'));
    }
}