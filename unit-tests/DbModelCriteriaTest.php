<?php

use ManaPHP\Mvc\Model\Criteria;
use Models\City;

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class DbModelCriteriaTest extends TestCase
{
    public function setUp()
    {
        $di = new \ManaPHP\Di\FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        $di->db = $db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
        // $this->db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

        $db->attachEvent('db:beforeQuery', function (\ManaPHP\DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        });

        echo get_class($db), PHP_EOL;
    }

    public function test_select()
    {
        $this->assertEquals('SELECT [city_id] FROM [city]',
            City::createCriteria()->select('city_id')->getSql());
    }

    public function test_where()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            City::createCriteria()->where('city_id', 1)->getSql());
    }

    public function test_betweenWhere()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] BETWEEN :city_id_min AND :city_id_max',
            City::createCriteria()->betweenWhere('city_id', 1, 10)->getSql());

    }

    public function test_notBetweenWhere()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT BETWEEN :_min_0 AND :_max_0',
            City::createCriteria()->notBetweenWhere('city_id', 1, 10)->getSql());
    }

    public function test_inWhere()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE 1=2',
            City::createCriteria()->inWhere('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (:_in_0_0)',
            City::createCriteria()->inWhere('city_id', [1])->getSql());
    }

    public function test_notInWhere()
    {
        $this->assertEquals('SELECT * FROM [city]',
            City::createCriteria()->notInWhere('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (:_in_0_0)',
            City::createCriteria()->notInWhere('city_id', [1])->getSql());
    }

    public function test_likeWhere()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_name] LIKE :city_name',
            City::createCriteria()->likeWhere('city_name', '%A%')->getSql());
    }

    public function test_limit()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10',
            City::createCriteria()->limit(10)->getSql());
    }

    public function test_page()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            City::createCriteria()->page(10, 3)->getSql());
    }

    public function test_orderBy()
    {
        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id]',
            City::createCriteria()->orderBy('city_id')->getSql());
    }

    public function test_groupBy()
    {
        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id]',
            City::createCriteria()->groupBy('city_id')->getSql());
    }

    public function test_exists()
    {
        $this->assertTrue(City::createCriteria()->exists());
        $this->assertTrue(City::createCriteria()->where('city_id', 1)->exists());
        $this->assertFalse(City::createCriteria()->where('city_id', 0)->exists());
    }
}