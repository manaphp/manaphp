<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mongodb;
use PHPUnit\Framework\TestCase;

class MongodbTest extends TestCase
{
    /**
     * @var \ManaPHP\MongodbInterface
     */
    protected $mongodb;

    public function setUp()
    {
        new FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        //$this->db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);

        $this->mongodb = new Mongodb($config['mongodb']);
    }

    public function test_query()
    {
        //general usage
        $documents = $this->mongodb->fetchAll('city', [], ['limit' => 3]);
        $this->assertCount(3, $documents);
    }
}