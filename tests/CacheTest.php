<?php

namespace Tests;

use ManaPHP\Cache;
use ManaPHP\Cache\Engine\File;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function test_construct()
    {
        //default
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $cache = new Cache();
        $cache->setDi($di);

        $this->assertAttributeSame(File::class, '_engine', $cache);
        $cache->get('xxx');
        $this->assertAttributeInstanceOf(File::class, '_engine', $cache);
        $this->assertAttributeSame('', '_prefix', $cache);

        //instance
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $file = new File();
        $cache = new Cache($file);
        $this->assertAttributeSame($file, '_engine', $cache);
        $this->assertAttributeSame('', '_prefix', $cache);

        //class name string
        $cache = new Cache(File::class);
        $cache->setDi($di);

        $this->assertAttributeSame(File::class, '_engine', $cache);
        $cache->get('abc');
        $this->assertAttributeInstanceOf(File::class, '_engine', $cache);
        $this->assertAttributeSame('', '_prefix', $cache);

        //component name string
        $di->setShared('fileCacheEngine', File::class);
        $cache = new Cache('fileCacheEngine');
        $cache->setDi($di);

        $this->assertAttributeSame('fileCacheEngine', '_engine', $cache);
        $cache->get('abc');
        $this->assertAttributeInstanceOf(File::class, '_engine', $cache);
        $this->assertAttributeSame('', '_prefix', $cache);

        //array
        $cache = new Cache(['engine' => File::class, 'prefix' => 'AAA']);
        $cache->setDi($di);

        $this->assertAttributeSame(File::class, '_engine', $cache);
        $cache->get('abc');
        $this->assertAttributeInstanceOf(File::class, '_engine', $cache);
        $this->assertAttributeSame('AAA', '_prefix', $cache);

        //array
        $cache = new Cache(['engine' => ['class' => File::class, 'dir' => 'xxx']]);
        $cache->setDi($di);

        $this->assertAttributeSame(['class' => File::class, 'dir' => 'xxx'], '_engine', $cache);
        $cache->get('abc');
        $this->assertAttributeInstanceOf(File::class, '_engine', $cache);

        $this->assertAttributeSame('', '_prefix', $cache);
    }

    public function test_exists()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $cache = new Cache(new File());
        $cache->delete('country');

        $this->assertFalse($cache->exists('country'));

        $cache->set('country', 'china', 100);
        $this->assertTrue($cache->exists('country'));
    }

    public function test_get()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $cache = new Cache(new File());

        $cache->delete('country');
        $this->assertFalse($cache->get('country'));

        $cache->set('country', 'china', 100);
        $this->assertEquals('china', $cache->get('country'));
    }

    public function test_set()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $cache = new Cache(new File());
        $cache->delete('var');

        $this->assertFalse($cache->get('var'));

        //null
        $cache->set('var', null, 100);
        $this->assertNull($cache->get('var'));

        // false, not support
        try {
            $cache->set('var', false, 100);
            $this->fail('why not!');
        } catch (\Exception $e) {
            $this->assertEquals('`var` key cache value can not `false` boolean value', $e->getMessage());
        }

        // true
        $cache->set('var', true, 100);
        $this->assertSame(true, $cache->get('var'));

        // int
        $cache->set('var', 199, 100);
        $this->assertSame(199, $cache->get('var'));

        // float
        $cache->set('var', 1.5, 100);
        $this->assertSame(1.5, $cache->get('var'));

        //string
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '', 100);
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', '{', 100);
        $this->assertSame('{', $cache->get('var'));

        $cache->set('var', '[', 100);
        $this->assertSame('[', $cache->get('var'));

        //array
        $cache->set('var', [1, 2, 3], 100);
        $this->assertSame([1, 2, 3], $cache->get('var'));

        $cache->set('var', ['_wrapper_' => 123], 100);
        $this->assertSame(['_wrapper_' => 123], $cache->get('var'));

        //object
        $value = new \stdClass();
        $value->a = 123;
        $value->b = 'bbbb';

        $cache->set('val', $value, 100);
        $this->assertEquals((array)$value, $cache->get('val'));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

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