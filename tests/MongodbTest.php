<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/23
 * Time: 21:36
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MongodbTest extends TestCase
{
    /**
     * @var \ManaPHP\MongodbInterface
     */
    protected $mongodb;

    public function setUp()
    {
        new \ManaPHP\Di\FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        //$this->db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);

        $this->mongodb = new ManaPHP\Mongodb($config['mongodb']);
    }

    public function test_query()
    {
        //general usage
        $documents = $this->mongodb->query('city', [], ['limit' => 3]);
        $this->assertCount(3, $documents);
    }
}