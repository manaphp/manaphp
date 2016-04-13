<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CachingStoreTest extends TestCase
{
    public function test_exists()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());

        $this->assertFalse($cache->exists('country'));

        $cache->set('country', 'china');
        $this->assertTrue($cache->exists('country'));
    }

    public function test_get()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());
        $this->assertFalse($cache->get('country'));

        $cache->set('country', 'china');
        $this->assertEquals('china', $cache->get('country'));
    }

    public function test_set()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());
        $this->assertFalse($cache->get('var'));

        // false
        $cache->set('var', false);
        $this->assertEquals(false, $cache->get('var'));
        $this->assertSame(false, $cache->get('val'));
        // true
        $cache->set('var', true);
        $this->assertSame(true, $cache->get('var'));

        // int
        $cache->set('var', 199);
        $this->assertSame(199, $cache->get('var'));

        //string
        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));

        //array
        $cache->set('var', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $cache->get('var'));

        $value = new stdClass();
        $value->a = 123;
        $value->b = 'bbbb';

        // object and save as object
        $cache->set('val', $value);
        $this->assertEquals($value, $cache->get('val'));
        $this->assertInstanceOf('\stdClass', $cache->get('val'));

        // object and save as array
        $cache->set('val', (array)$value);
        $this->assertEquals((array)$value, $cache->get('val'));
        $this->assertTrue(is_array($cache->get('val')));
    }

    public function test_mGet()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());

        $cache->set('1', '1');
        $idValues = $cache->mGet(['1', '2']);

        $this->assertEquals('1', $idValues['1']);
        $this->assertFalse($idValues[2]);
    }

    public function test_mSet()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());

        $cache->mSet([]);

        $cache->mSet(['1' => 1, '2' => 2]);
        $this->assertSame(1, $cache->get(1));
        $this->assertSame(2, $cache->get(2));
        $this->assertFalse($cache->get(3));
    }

    public function test_delete()
    {
        $cache = new ManaPHP\Caching\Store('', new \ManaPHP\Caching\Store\Adapter\Memory());

        // delete a not existed
        $this->assertFalse($cache->exists('val'));
        $cache->delete('val');

        // delete an existed
        $cache->set('country', 'china');
        $this->assertTrue($cache->exists('country'));
        $cache->delete('country');
        $this->assertFalse($cache->exists('country'));
    }
}