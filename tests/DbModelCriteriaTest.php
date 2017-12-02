<?php

namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mvc\Model\Criteria;
use PHPUnit\Framework\TestCase;
use Tests\Models\Actor;
use Tests\Models\City;
use Tests\Models\Payment;

class DbModelCriteriaTest extends TestCase
{
    public function setUp()
    {
        $di = new FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        $di->db = $db = new Mysql($config['mysql']);
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
            City::criteria()->select('city_id')->getSql());
    }

    public function test_where()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            City::criteria()->where('city_id', 1)->getSql());
    }

    public function test_whereRaw()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE city_id >1',
            City::criteria()->whereRaw('city_id >1')->getSql());
    }

    public function test_whereBetween()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] BETWEEN :city_id_min AND :city_id_max',
            City::criteria()->whereBetween('city_id', 1, 10)->getSql());

    }

    public function test_whereNotBetween()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT BETWEEN :_min_0 AND :_max_0',
            City::criteria()->whereNotBetween('city_id', 1, 10)->getSql());
    }

    public function test_whereIn()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE 1=2',
            City::criteria()->whereIn('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (:_in_0_0)',
            City::criteria()->whereIn('city_id', [1])->getSql());
    }

    public function test_whereNotIn()
    {
        $this->assertEquals('SELECT * FROM [city]',
            City::criteria()->whereNotIn('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (:_in_0_0)',
            City::criteria()->whereNotIn('city_id', [1])->getSql());
    }

    public function test_whereContains()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_name] LIKE :city_name',
            City::criteria()->whereContains('city_name', 'A')->getSql());
    }

    public function test_whereStartsWith()
    {
        $this->assertEquals(43, City::criteria()->whereStartsWith('city', 'A')->count());
        $this->assertEquals(4, City::criteria()->whereStartsWith('city', 'A', 4)->count());
    }

    public function test_whereEndsWith()
    {
        $this->assertEquals(125, City::criteria()->whereEndsWith('city', 'a')->count());
    }

    public function test_whereLike()
    {
        $this->assertEquals(0, City::criteria()->whereLike('city', 'A')->count());
        $this->assertEquals(43, City::criteria()->whereLike('city', 'A%')->count());
        $this->assertEquals(125, City::criteria()->whereLike('city', '%A')->count());
        $this->assertEquals(450, City::criteria()->whereLike('city', '%A%')->count());
        $this->assertEquals(4, City::criteria()->whereLike('city', 'A___')->count());
        $this->assertEquals(83, City::criteria()->whereLike('city', '%A___')->count());
    }

    public function test_whereRegex()
    {
        $this->assertEquals(46, City::criteria()->whereRegex('city', 'A')->count());
        $this->assertEquals(125, City::criteria()->whereRegex('city', 'a$')->count());
        $this->assertEquals(38, City::criteria()->whereRegex('city', '^A')->count());
        $this->assertEquals(262, City::criteria()->whereRegex('city', 'a....')->count());
        $this->assertEquals(34, City::criteria()->whereRegex('city', '^A....')->count());
    }

    public function test_whereNotRegex()
    {
        $this->assertEquals(554, City::criteria()->whereNotRegex('city', 'A')->count());
        $this->assertEquals(475, City::criteria()->whereNotRegex('city', 'a$')->count());
        $this->assertEquals(562, City::criteria()->whereNotRegex('city', '^A')->count());
        $this->assertEquals(338, City::criteria()->whereNotRegex('city', 'a....')->count());
        $this->assertEquals(566, City::criteria()->whereNotRegex('city', '^A....')->count());
    }

    public function test_whereNull()
    {
        $this->assertEquals(0, City::criteria()->whereNull('city_id')->count());
    }

    public function test_whereNotNull()
    {
        $this->assertEquals(600, City::criteria()->whereNotNull('city_id')->count());
    }

    public function test_limit()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10',
            City::criteria()->limit(10)->getSql());
    }

    public function test_page()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            City::criteria()->page(10, 3)->getSql());
    }

    public function test_orderBy()
    {
        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id]',
            City::criteria()->orderBy('city_id')->getSql());
    }

    public function test_groupBy()
    {
        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id]',
            City::criteria()->groupBy('city_id')->getSql());
    }

    public function test_exists()
    {
        $this->assertTrue(City::criteria()->exists());
        $this->assertTrue(City::criteria()->where('city_id', 1)->exists());
        $this->assertFalse(City::criteria()->where('city_id', 0)->exists());
    }

    public function test_count()
    {
        $this->assertTrue(is_int(Actor::criteria()->count()));

        $this->assertEquals(200, Actor::criteria()->count());

        $this->assertEquals(1, Actor::criteria()->where(['actor_id' => 1])->count());

        $this->assertEquals(128, Actor::criteria()->count(' DISTINCT first_name'));
    }

    public function test_sum()
    {
        $sum = Payment::criteria()->sum('amount');
        $this->assertEquals('string', gettype($sum));
        $this->assertEquals(67417.0, round($sum, 0));

        $sum = Payment::criteria()->where(['customer_id' => 1])->sum('amount');
        $this->assertEquals('118.68', $sum);
    }

    public function test_max()
    {
        $max = Payment::criteria()->max('amount');
        $this->assertEquals('string', gettype($max));
        $this->assertEquals('11.99', $max);
    }

    public function test_min()
    {
        $min = Payment::criteria()->min('amount');
        $this->assertEquals('string', gettype($min));
        $this->assertEquals('0.00', $min);
    }

    public function test_avg()
    {
        $avg = Payment::criteria()->avg('amount');
        $this->assertEquals('double', gettype($avg));

        $this->assertEquals(4.20, round($avg, 2));
    }
}