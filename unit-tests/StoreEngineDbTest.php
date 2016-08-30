<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class StoreEngineDbTest extends TestCase
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
        $store = new \ManaPHP\Store\Engine\Db();

        $store->delete('var');
        $this->assertFalse($store->exists('var'));
        $store->set('var', 'value');
        $this->assertTrue($store->exists('var'));
    }

    public function test_get()
    {
        $store = new \ManaPHP\Store\Engine\Db();

        $store->delete('var');

        $this->assertFalse($store->get('var'));
        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));
    }

    public function test_set()
    {
        $store = new \ManaPHP\Store\Engine\Db();

        $store->set('var', '');
        $this->assertSame('', $store->get('var'));

        $store->set('var', 'value');
        $this->assertSame('value', $store->get('var'));

        $store->set('var', '{}');
        $this->assertSame('{}', $store->get('var'));
    }

    public function test_delete()
    {
        $store = new \ManaPHP\Store\Engine\Db();

        //exists and delete
        $store->set('var', 'value');
        $store->delete('var');

        // missing and delete
        $store->delete('var');
    }
}