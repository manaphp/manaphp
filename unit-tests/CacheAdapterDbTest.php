<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CacheAdapterDbTest extends TestCase
{
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new ManaPHP\Di\FactoryDefault();

        $this->_di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
            $db->attachEvent('db:beforeQuery', function (\ManaPHP\DbInterface $source, $data) {
                //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getSQL(), $source->getEmulatedSQL(2));

            });
            return $db;
        });
    }

    public function test_exists()
    {
        $cache = new \ManaPHP\Cache\Adapter\Db();

        $cache->delete('var');
        $this->assertFalse($cache->exists('var'));
        $cache->set('var', 'value', 1000);
        $this->assertTrue($cache->exists('var'));
    }

    public function test_get()
    {
        $cache = new \ManaPHP\Cache\Adapter\Db();

        $cache->delete('var');

        $this->assertFalse($cache->get('var'));
        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));
    }

    public function test_set()
    {
        $cache = new \ManaPHP\Cache\Adapter\Db();

        $cache->set('var', '', 100);
        $this->assertSame('', $cache->get('var'));

        $cache->set('var', 'value', 100);
        $this->assertSame('value', $cache->get('var'));

        $cache->set('var', '{}', 100);
        $this->assertSame('{}', $cache->get('var'));

        // ttl
        $cache->set('var', 'value', 1);
        $this->assertTrue($cache->exists('var'));
        sleep(2);
        $this->assertFalse($cache->exists('var'));
    }

    public function test_delete()
    {
        $cache = new \ManaPHP\Cache\Adapter\Db();

        //exists and delete
        $cache->set('var', 'value', 100);
        $cache->delete('var');

        // missing and delete
        $cache->delete('var');
    }
}