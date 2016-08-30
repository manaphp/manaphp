<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class StoreAdapterMemoryTest extends TestCase
{
    public function test_exists()
    {
        $cache = new \ManaPHP\Store\Engine\Memory();
        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value');
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Store\Engine\Memory();
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Store\Engine\Memory();

        $cache->set('var', '');
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '{}');
        $this->assertSame('{}', $cache->get('var'));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Store\Engine\Memory();

        //exists and delete
        $cache->set('var', 'value');
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}