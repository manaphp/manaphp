<?php

namespace Tests;

use ManaPHP\Store\Engine\Memory;
use PHPUnit\Framework\TestCase;

class StoreEngineMemoryTest extends TestCase
{
    public function test_exists()
    {
        $cache = new Memory();
        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value');
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $cache = new Memory();
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        $cache = new Memory();

        $cache->set('var', '');
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', 'value');
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '{}');
        $this->assertSame('{}', $cache->get('var'));
    }

    public function test_delete()
    {
        $cache = new Memory();

        //exists and delete
        $cache->set('var', 'value');
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}