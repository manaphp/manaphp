<?php

namespace Tests;

use ManaPHP\Data\Db;
use ManaPHP\Data\Db\Adapter\Proxy;
use ManaPHP\Data\Db\Adapter\Sqlite;
use ManaPHP\Data\Db\Query;
use ManaPHP\Data\DbInterface;
use ManaPHP\Di\Container;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use Tests\Models\Actor;
use Tests\Models\Address;
use Tests\Models\City;
use Tests\Models\Country;
use Tests\Models\Payment;

class DataDbModelQueryTest extends TestCase
{
    public function setUp()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp/data');

        $di->setShared(
            'db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Db($config['mysql']);

            $db->attachEvent(
                'db:beforeQuery', function (DbInterface $source) {
                var_dump($source->getSQL());
                var_dump($source->getEmulatedSQL());
            }
            );

            echo get_class($db), PHP_EOL;
            return $db;
        }
        );
    }

    public function test_select()
    {
        $this->assertEquals(
            'SELECT [city_id] FROM [city]',
            City::query()->select(['city_id'])->getSql()
        );
    }


    public function test_distinct()
    {
        //select all implicitly
        $query = Address::query()->select(['city_id']);
        $this->assertCount(603, $query->execute());

        //select all explicitly
        $query = Address::query()
            ->select(['city_id'])
            ->distinct(false);
        $this->assertCount(603, $query->execute());

        //select distinct
        $query = Address::query()
            ->select(['city_id'])
            ->distinct(true);
        $this->assertCount(599, $query->execute());
    }

    public function test_columns()
    {
        //default select all columns
        $query = Address::query()->limit(2);
        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(8, $rows[0]);

        //select all columns explicitly
        $query = Address::query()->select(['*'])->limit(2);
        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(8, $rows[0]);

        // select all columns explicitly and use table alias
        $query = Address::query('a')
            ->select(['a.*', 'c.*'])
            ->leftJoin(get_class(new City()), 'c.city_id =a.city_id', 'c')
            ->limit(2);
        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(10, $rows[0]);

        //string format columns
        $query = Address::query('a')
            ->select(['a.address_id', 'a.address', 'a.phone'])
            ->limit(2);
        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //dense multi space to only one for columns
        $query = Address::query('a')
            ->select(['a.address_id', 'a.address', 'c.city'])
            ->leftJoin(get_class(new City()), 'c.city_id =a.city_id', 'c')
            ->limit(2)
            ->orderBy(['a.address_id' => SORT_ASC]);
        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        $query = Address::query()
            ->select(['address_count' => 'count(address_id)']);
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]);
    }

    public function test_from()
    {
        $query = Address::query()
            ->select(['address_id', 'address', 'phone'])
            ->limit(2);

        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);
    }

    public function test_addFrom()
    {
        //one model without alias
        $query = Address::query()
            ->select(['address_id', 'address', 'phone'])
            ->limit(2);

        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //one model with alias
        $query = Address::query('a')
            ->select(['a.address_id', 'a.address', 'a.phone'])
            ->limit(2);

        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //multi-models with alias
        $query = Address::query('a')
            ->select(['a.*', 'c.*'])
            ->addFrom(get_class(new City()), 'c')
            ->limit(2);

        $rows = $query->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(10, $rows[0]);

        $query = (new Query())
            ->select(['*'])
            ->from(
                City::query()
                    ->select(['*'])
                    ->where(['city_id<=' => 5]), 'cc'
            );
        $rows = $query->execute();
        $this->assertCount(5, $rows);
    }

    public function test_join()
    {
        if (Container::getDefault()->get('db') instanceof Sqlite) {
            return;
        }

        //with model
        $query = Address::query()
            ->select(['address_count' => 'count(address_id)'])
            ->join(get_class(new City()));
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(361800, $rows[0]['address_count']);

        //with model,conditions
        $query = Address::query('a')
            ->select(['address_count' => 'count(address_id)'])
            ->join(get_class(new City()), 'city.city_id =a.city_id');
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(603, $rows[0]['address_count']);

        //with model,conditions,alias
        $query = Address::query('a')
            ->select(['address_count' => 'count(address_id)'])
            ->join(get_class(new City()), 'c.city_id =a.city_id', 'c');
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(603, $rows[0]['address_count']);

        //with model,conditions,alias,join
        $query = Address::query('a')
            ->select(['a.address_id', 'a.address', 'a.city_id', 'c.city'])
            ->join(get_class(new City()), 'c.city_id =a.city_id', 'c', 'LEFT');
        $rows = $query->execute();
        $this->assertCount(603, $rows);

        $query = Address::query('a')
            ->select(['a.address_id', 'a.address', 'a.city_id', 'c.city'])
            ->join(
                City::query()
                    ->select(['*'])
                    ->where(['city_id<' => 50]), 'c.city_id =a.city_id', 'c', 'RIGHT'
            );
        $rows = $query->execute();
        $this->assertCount(50, $rows);
    }

    public function test_innerJoin()
    {
        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $builder = City::query('c1')
            ->select(['c1.*', 'c2.*'])
            ->innerJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCountry, $builder->execute());
    }

    public function test_leftJoin()
    {
        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $query = City::query('c1')
            ->select(['c1.*', 'c2.*'])
            ->leftJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCity, $query->execute());
    }

    public function test_rightJoin()
    {
        if (Container::getDefault()->get('db') instanceof Sqlite) {
            return;
        }

        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $query = City::query('c1')
            ->select(['c1.*', 'c2.*'])
            ->rightJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCountry, $query->execute());
    }

    public function test_where()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id]=:city_id',
            City::where(['city_id' => 1])->getSql()
        );

        $query = Address::where(['address_id<=' => 100]);
        $this->assertCount(100, $query->execute());

        $query = Address::where(['address_id<=' => 100]);
        $this->assertCount(100, $query->execute());

        $query = Address::where(['address_id' => 100]);
        $this->assertCount(1, $query->execute());

        $query = Address::where(['address_id~=' => [51, 100]]);
        $this->assertCount(50, $query->execute());
    }

    public function test_whereRaw()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE city_id >1',
            City::query()->whereRaw('city_id >1')->getSql()
        );
    }

    public function test_whereBetween()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] BETWEEN :city_id_min AND :city_id_max',
            City::query()->whereBetween('city_id', 1, 10)->getSql()
        );

        $this->assertCount(50, Address::query()->whereBetween('address_id', 51, 100)->execute());
        $this->assertCount(100, Address::query()->whereBetween('address_id', null, 100)->execute());
        $this->assertCount(100, Address::query()->whereBetween('address_id', '', 100)->execute());
        $this->assertCount(504, Address::query()->whereBetween('address_id', 100, null)->execute());
        $this->assertCount(504, Address::query()->whereBetween('address_id', 100, '')->execute());
    }

    public function test_whereNotBetween()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] NOT BETWEEN :_min_0 AND :_max_0',
            City::query()->whereNotBetween('city_id', 1, 10)->getSql()
        );

        $this->assertCount(553, Address::query()->whereNotBetween('address_id', 51, 100)->execute());
        $this->assertCount(503, Address::query()->whereNotBetween('address_id', null, 100)->execute());
        $this->assertCount(503, Address::query()->whereNotBetween('address_id', '', 100)->execute());
        $this->assertCount(50, Address::query()->whereNotBetween('address_id', 51, null)->execute());
        $this->assertCount(50, Address::query()->whereNotBetween('address_id', 51, '')->execute());
        $query = Address::query()
            ->whereNotBetween('address_id', 51, 1000000)
            ->whereNotBetween('address_id', 71, 7000000);
        $this->assertCount(50, $query->execute());
    }

    public function test_whereIn()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE FALSE',
            City::query()->whereIn('city_id', [])->getSql()
        );

        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] IN (1)',
            City::query()->whereIn('city_id', [1])->getSql()
        );

        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] IN (:_in_0_0)',
            City::query()->whereIn('city_id', ['1'])->getSql()
        );

        $query = Address::query()->whereIn('address_id', []);
        $this->assertCount(0, $query->execute());

        $query = Address::query()->whereIn('address_id', [1]);
        $this->assertCount(1, $query->execute());

        $query = Address::query()
            ->whereIn('address_id', [1, 2, 3, 4, 5]);
        $this->assertCount(5, $query->execute());

        $query = Address::query()
            ->whereIn('address_id', [-3, -2, -1, 0, 1, 2]);
        $this->assertCount(2, $query->execute());

        $query = Address::query()
            ->select(['*'])
            ->whereIn(
                'city_id', Address::query()
                ->select(['city_id'])
                ->whereIn('city_id', [1, 2, 3, 4])
            );
        $this->assertCount(4, $query->execute());
    }

    public function test_whereNotIn()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city]',
            City::query()->whereNotIn('city_id', [])->getSql()
        );

        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] NOT IN (1)',
            City::query()->whereNotIn('city_id', [1])->getSql()
        );

        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_id] NOT IN (:_in_0_0)',
            City::query()->whereNotIn('city_id', ['1'])->getSql()
        );
    }

    public function test_whereContains()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_name] LIKE :city_name',
            City::query()->whereContains('city_name', 'A')->getSql()
        );

        $documents = Address::query()->whereContains('address', 'as')->fetch();
        $this->assertCount(24, $documents);
        $documents = Address::query()->whereContains('district', 'as')->fetch();
        $this->assertCount(48, $documents);

        $documents = Address::query()->whereContains(['address', 'district'], 'as')->fetch();
        $this->assertCount(71, $documents);
    }

    public function test_whereNotContains()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] WHERE [city_name] NOT LIKE :city_name',
            City::query()->whereNotContains('city_name', 'A')->getSql()
        );
    }

    public function test_whereStartsWith()
    {
        $this->assertEquals(43, City::query()->whereStartsWith('city', 'A')->count());
        $this->assertEquals(4, City::query()->whereStartsWith('city', 'A', 4)->count());
    }

    public function test_whereNotStartsWith()
    {
        $this->assertEquals(557, City::query()->whereNotStartsWith('city', 'A')->count());
        $this->assertEquals(596, City::query()->whereNotStartsWith('city', 'A', 4)->count());
    }

    public function test_whereEndsWith()
    {
        $this->assertEquals(125, City::query()->whereEndsWith('city', 'a')->count());
    }

    public function test_whereNotEndsWith()
    {
        $this->assertEquals(475, City::query()->whereNotEndsWith('city', 'a')->count());
    }

    public function test_whereLike()
    {
        $this->assertEquals(0, City::query()->whereLike('city', 'A')->count());
        $this->assertEquals(43, City::query()->whereLike('city', 'A%')->count());
        $this->assertEquals(125, City::query()->whereLike('city', '%a')->count());
        $this->assertEquals(450, City::query()->whereLike('city', '%A%')->count());
        $this->assertEquals(4, City::query()->whereLike('city', 'A___')->count());
        $this->assertEquals(83, City::query()->whereLike('city', '%A___')->count());
    }

    public function test_whereNotLike()
    {
        $this->assertEquals(600, City::query()->whereNotLike('city', 'A')->count());
        $this->assertEquals(557, City::query()->whereNotLike('city', 'A%')->count());
        $this->assertEquals(475, City::query()->whereNotLike('city', '%A')->count());
        $this->assertEquals(150, City::query()->whereNotLike('city', '%A%')->count());
        $this->assertEquals(596, City::query()->whereNotLike('city', 'A___')->count());
        $this->assertEquals(517, City::query()->whereNotLike('city', '%A___')->count());
    }

    public function test_whereRegex()
    {
        $this->assertEquals(46, City::query()->whereRegex('city', 'A')->count());
        $this->assertEquals(125, City::query()->whereRegex('city', 'a$')->count());
        $this->assertEquals(38, City::query()->whereRegex('city', '^A')->count());
        $this->assertEquals(262, City::query()->whereRegex('city', 'a....')->count());
        $this->assertEquals(34, City::query()->whereRegex('city', '^A....')->count());

        $this->assertEquals(450, City::query()->whereRegex('city', 'a', 'i')->count());
        $this->assertEquals(450, City::query()->whereRegex('city', 'A', 'i')->count());
    }

    public function test_whereNotRegex()
    {
        $this->assertEquals(554, City::query()->whereNotRegex('city', 'A')->count());
        $this->assertEquals(475, City::query()->whereNotRegex('city', 'a$')->count());
        $this->assertEquals(562, City::query()->whereNotRegex('city', '^A')->count());
        $this->assertEquals(338, City::query()->whereNotRegex('city', 'a....')->count());
        $this->assertEquals(566, City::query()->whereNotRegex('city', '^A....')->count());

        $this->assertEquals(150, City::query()->whereNotRegex('city', 'a', 'i')->count());
        $this->assertEquals(150, City::query()->whereNotRegex('city', 'A', 'i')->count());
    }

    public function test_whereNull()
    {
        $this->assertEquals(0, City::query()->whereNull('city_id')->count());
    }

    public function test_whereNotNull()
    {
        $this->assertEquals(600, City::query()->whereNotNull('city_id')->count());
    }

    public function test_orderBy()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] ORDER BY [city_id]',
            City::query()->orderBy('city_id')->getSql()
        );

        $query = Address::query()
            ->select(['address_id'])
            ->where(['address_id<=' => 10])
            ->orderBy('address_id');
        $rows = $query->execute();
        $this->assertCount(10, $query->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] < $rows[$i + 1]['address_id']);
        }

        $query = Address::query()
            ->select(['address_id'])
            ->where(['address_id<=' => 10])
            ->orderBy('address_id ASC');
        $rows = $query->execute();
        $this->assertCount(10, $query->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] < $rows[$i + 1]['address_id']);
        }

        $query = Address::query()
            ->select(['address_id'])
            ->where(['address_id<=' => 10])
            ->orderBy('address_id DESC');
        $rows = $query->execute();
        $this->assertCount(10, $query->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] > $rows[$i + 1]['address_id']);
        }
    }

    public function test_indexBy()
    {
        $query = Address::query()
            ->select(['address_id'])
            ->where(['address_id>=' => 5])
            ->indexBy('address_id')
            ->limit(1);
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('5', $rows);

        $query = Address::query()
            ->select(['address_id', 'address'])
            ->where(['address_id>=' => 5])
            ->indexBy(['address_id' => 'address'])
            ->limit(1);
        $rows = $query->execute();
        $this->assertEquals([5 => '1913 Hanoi Way'], $rows);

        $query = Address::where(['address_id>=' => 5])
            ->indexBy(['address_id' => 'address'])
            ->limit(1);
        $rows = $query->execute();
        $this->assertEquals([5 => '1913 Hanoi Way'], $rows);

        $query = Address::query()
            ->select(['address_id'])
            ->where(['address_id>=' => 5])
            ->indexBy(
                function ($row) {
                    return 'address_' . $row['address_id'];
                }
            )
            ->limit(1);
        $rows = $query->execute();
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('address_5', $rows);
    }

    public function test_having()
    {
        $query = City::query()
            ->select(['count_city' => 'COUNT(city_id)', 'country_id'])
            ->groupBy('country_id')
            ->having('COUNT(city_id) >1');
        $rows = $query->execute();
        $this->assertCount(67, $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row['count_city'] > 1);
        }

        $query = City::query()
            ->select(['count_city' => 'COUNT(city_id)', 'country_id'])
            ->groupBy('country_id')
            ->having('COUNT(city_id) >1 AND COUNT(city_id) <7');
        $rows = $query->execute();
        $this->assertCount(46, $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row['count_city'] > 1);
            $this->assertTrue($row['count_city'] < 7);
        }

        $query = City::query()
            ->select(['count_city' => 'COUNT(city_id)', 'country_id'])
            ->groupBy('country_id')
            ->having('COUNT(city_id) >:min_count AND COUNT(city_id) <:max_count', ['min_count' => 1, 'max_count' => 7]);
        $rows = $query->execute();
        $this->assertCount(46, $rows);
    }

    public function test_limit()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] LIMIT 10',
            City::query()->limit(10)->getSql()
        );

        //limit without offset
        $query = City::query()->select(['city_id'])->limit(1);
        $this->assertCount(1, $query->execute());

        //limit with offset
        $query = City::query()
            ->select(['city_id'])
            ->orderBy(['city_id' => SORT_ASC])
            ->limit(10, 20);

        $rows = $query->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals(21, $rows[0]['city_id']);
        $this->assertEquals(30, $rows[9]['city_id']);
    }

    public function test_page()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] LIMIT 10 OFFSET 20',
            City::query()->page(10, 3)->getSql()
        );

        //limit without offset
        $query = City::query()->select(['city_id'])->limit(1);
        $this->assertCount(1, $query->execute());

        //limit with offset
        $query = City::query()
            ->select(['city_id'])
            ->orderBy(['city_id' => SORT_ASC])
            ->page(10, 3);

        $rows = $query->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals(21, $rows[0]['city_id']);
        $this->assertEquals(30, $rows[9]['city_id']);
    }

    public function test_groupBy()
    {
        $this->assertEquals(
            'SELECT [city_id], [city], [country_id], [last_update] FROM [city] GROUP BY [city_id]',
            City::query()->groupBy('city_id')->getSql()
        );

        $query = City::query()
            ->select(['count_city' => 'COUNT(city_id)', 'country_id'])
            ->groupBy('country_id');
        $rows = $query->execute();
        $this->assertCount(109, $rows);

        $query = Payment::query()
            ->select(['payment_times' => 'COUNT(payment_id)', 'customer_id', 'amount'])
            ->groupBy('customer_id,amount');
        $rows = $query->execute();
        $this->assertCount(4812, $rows);
    }

    public function test_whereNotIn2()
    {
        $rowAddress = Address::count();
        $this->assertEquals(603, $rowAddress);

        $query = Address::query()
            ->whereNotIn('address_id', []);
        $this->assertCount(603, $query->execute());

        $query = Address::query()
            ->whereNotIn('address_id', [1]);
        $this->assertCount(602, $query->execute());

        $query = Address::query()
            ->whereNotIn('address_id', [1, 2, 3]);
        $this->assertCount(600, $query->execute());

        $query = Address::query()
            ->whereNotIn('address_id', [-3, -2, -1, 0, 1, 2]);
        $this->assertCount(601, $query->execute());

        $query = Address::query()
            ->select(['*'])
            ->whereNotIn(
                'city_id', Address::query()
                ->select(['city_id'])
                ->whereIn('city_id', [1, 2, 3, 4])
            );
        $this->assertCount(599, $query->execute());
    }

    public function test_exists()
    {
        $this->assertTrue(City::query()->exists());
        $this->assertTrue(City::where(['city_id' => 1])->exists());
        $this->assertFalse(City::where(['city_id' => 0])->exists());
    }

    public function test_count()
    {
        $this->assertInternalType('int', Actor::query()->count());

        $this->assertEquals(200, Actor::query()->count());

        $this->assertEquals(1, Actor::query()->where(['actor_id' => 1])->count());

        $this->assertEquals(128, Actor::query()->count(' DISTINCT first_name'));
    }

    public function test_sum()
    {
        $sum = Payment::query()->sum('amount');
        $this->assertEquals('string', gettype($sum));
        $this->assertEquals(67417.0, round($sum, 0));

        $sum = Payment::query()->where(['customer_id' => 1])->sum('amount');
        $this->assertEquals('118.68', $sum);
    }

    public function test_max()
    {
        $max = Payment::query()->max('amount');
        $this->assertEquals('string', gettype($max));
        $this->assertEquals('11.99', $max);
    }

    public function test_min()
    {
        $min = Payment::query()->min('amount');
        $this->assertEquals('string', gettype($min));
        $this->assertEquals('0.00', $min);
    }

    public function test_avg()
    {
        $avg = Payment::query()->avg('amount');
        $this->assertEquals('double', gettype($avg));

        $this->assertEquals(4.20, round($avg, 2));
    }

    public function test_paginate()
    {
        $pagination = City::query()->paginate(1000, 1);
        $this->assertCount(600, $pagination->items);
        $this->assertEquals(600, $pagination->count);

        $pagination = City::query()->paginate(100, 1);
        $this->assertCount(100, $pagination->items);
        $this->assertEquals(600, $pagination->count);

        $pagination = City::query()->paginate(1000, 1);
        $this->assertCount(600, $pagination->items);
        $this->assertEquals(600, $pagination->count);

        $pagination = City::query()->paginate(200, 2);
        $this->assertCount(200, $pagination->items);
        $this->assertEquals(600, $pagination->count);

        $pagination = City::query()->paginate(30, 100);
        $this->assertCount(0, $pagination->items);
        $this->assertEquals(600, $pagination->count);

        $pagination = City::query()->groupBy('country_id')->paginate(10, 2);
        $this->assertCount(10, $pagination->items);
        $this->assertEquals(109, $pagination->count);
    }

    public function test_unionAll()
    {
        if (Container::getDefault()->get('db') instanceof Sqlite) {
            return;
        }

        $query = (new Query())
            ->union(
                [
                    City::query()
                        ->select(['*'])
                        ->whereIn('city_id', [1, 2, 3, 4, 5])
                ]
            );
        $this->assertCount(5, $query->execute());

//        $builder = $this->modelsManager->createBuilder()
//            ->union([
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5])
//            ])
//            ->orderBy('city_id DESC');
//
//        $rows = $builder->execute();
//        $this->assertCount(10, $rows);
//        $this->assertEquals('5', $rows[0]['city_id']);
//        $this->assertEquals('5', $rows[1]['city_id']);

//        $builder = $this->modelsManager->createBuilder()
//            ->unionAll([
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5])
//            ])
//            ->orderBy('city_id ASC');

//        $rows = $builder->execute();
//        $this->assertCount(10, $rows);
//        $this->assertEquals('1', $rows[0]['city_id']);
//        $this->assertEquals('1', $rows[1]['city_id']);
//
//        $builder = $this->modelsManager->createBuilder()
//            ->union([
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5])
//            ])
//            ->orderBy('city_id ASC')
//            ->limit(5);
//
//        $rows = $builder->execute();
//        $this->assertCount(5, $rows);
//
//        $builder = $this->modelsManager->createBuilder()
//            ->unionDistinct([
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
//                $this->modelsManager->createBuilder()
//                    ->columns('*')
//                    ->from(City::class)
//                    ->inWhere('city_id', [1, 2, 3, 4, 5])
//            ]);
//
//        $rows = $builder->execute();
//        $this->assertCount(5, $rows);
    }
}