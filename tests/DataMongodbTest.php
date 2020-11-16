<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Data\Mongodb;
use PHPUnit\Framework\TestCase;

class DataMongodbTest extends TestCase
{
    /**
     * @var \ManaPHP\Data\MongodbInterface
     */
    protected $mongodb;

    public function setUp()
    {
        new FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        //$this->db = new ManaPHP\Data\Db\Adapter\Mysql($config['mysql']);

        $this->mongodb = new Mongodb($config['mongodb']);
    }

    public function test_query()
    {
        //general usage
        $documents = $this->mongodb->fetchAll('city', [], ['limit' => 3]);
        $this->assertCount(3, $documents);
    }
}