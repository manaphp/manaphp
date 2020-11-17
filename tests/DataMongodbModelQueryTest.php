<?php

namespace Tests;

use ManaPHP\Data\Mongodb;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use Tests\Mongodb\Models\Address;
use Tests\Mongodb\Models\City;

class DataMongodbModelQueryTest extends TestCase
{
    public function setUp()
    {
        $di = new FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        $di->setShared('mongodb', new Mongodb($config['mongodb']));
    }

    public function test_construct()
    {
        $document = City::query()->fetch();
        $this->assertEquals(['city_id', 'city', 'country_id', 'last_update'], array_keys($document[0]->toArray()));
    }

    public function test_values()
    {
        $this->assertCount(600, City::query()->values('city_id'));
        $this->assertCount(3, City::query()->where(['country_id' => 2])->values('city_id'));
    }

    public function test_select()
    {
        $documents = City::query()->limit(1)->fetch();
        $this->assertCount(1, $documents);
        $this->assertEquals(['city_id', 'city', 'country_id', 'last_update'], array_keys($documents[0]->toArray()));

        $documents = City::query()->select(['_id', 'city_id', 'city'])->limit(1)->fetch();
        $this->assertCount(1, $documents);
        $this->assertEquals(['city_id', 'city'], array_keys(array_filter($documents[0]->toArray())));

        $documents = City::query()->select(['_id', 'city_id', 'city'])->limit(1)->fetch();
        $this->assertCount(1, $documents);
        $this->assertEquals(['city_id', 'city'], array_keys(array_filter($documents[0]->toArray())));
    }

    public function test_aggregate()
    {
        $documents = City::query()->aggregate(['max' => 'MAX(city_id)']);
        $this->assertEquals(600, $documents[0]['max']);

        $documents = City::query()->aggregate(['min' => 'MIN(city_id)']);
        $this->assertEquals(1, $documents[0]['min']);

        $documents = City::query()->aggregate(['avg' => 'AVG(city_id)']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::query()->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertEquals(180300, $documents[0]['sum']);

        $documents = City::query()->aggregate(['avg' => 'AVG(city_id)']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::query()->aggregate(['sum' => 'SUM(city_id)', 'avg' => 'AVG(city_id)']);
        $this->assertEquals(180300, $documents[0]['sum']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::query()->aggregate(['max' => 'MAX(city_id+country_id)']);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::query()->aggregate(['max' => 'MAX(city_id + country_id)']);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::query()->aggregate(['max' => ['$max' => ['$add' => ['$city_id', '$country_id']]]]);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::query()->aggregate(['sum' => 'sum(city_id*country_id)']);
        $this->assertEquals(10003864, $documents[0]['sum']);

        $documents = City::query()->aggregate(['sum' => 'SUM(city_id*2.5)']);
        $this->assertEquals(450750, $documents[0]['sum']);
    }

    public function test_where()
    {
        /**
         * @var City $document
         */
        $document = City::where(['city_id' => 2])->fetch();
        $this->assertEquals(2, $document[0]->city_id);

        $document = City::where(['city_id' => '2'])->fetch();
        $this->assertEquals(2, $document[0]->city_id);

        $document = City::where(['city_id' => 2])->fetch();
        $this->assertEquals(2, $document[0]->city_id);

        $documents = City::where(['city_id' => -2])->fetch();
        $this->assertEmpty($documents);

        $documents = City::where(['city_id=' => 10])->fetch();
        $this->assertCount(1, $documents);
        $this->assertEquals(10, $documents[0]->city_id);

        $documents = City::where(['city_id>' => 10])->fetch();
        $this->assertCount(590, $documents);
        $this->assertEquals(11, $documents[0]->city_id);

        $documents = City::where(['city_id>=' => 10])->fetch();
        $this->assertCount(591, $documents);
        $this->assertEquals(10, $documents[0]->city_id);

        $documents = City::where(['city_id<' => 10])->fetch();
        $this->assertCount(9, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::where(['city_id<=' => 10])->fetch();
        $this->assertCount(10, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::where(['city_id!=' => 10])->fetch();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::where(['city_id<>' => 10])->fetch();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::where(['city^=' => 'Ab'])->fetch();
        $this->assertCount(2, $documents);
        $this->assertEquals(2, $documents[0]->city_id);
        $this->assertEquals(3, $documents[1]->city_id);

        $documents = City::where(['city$=' => 'a'])->fetch();
        $this->assertCount(125, $documents);

        $documents = City::where(['city*=' => 'a'])->fetch();
        $this->assertCount(450, $documents);

        $documents = City::where(['city_id' => [1, 2, 3, 4]])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::where(['city_id' => ['1', '2', '3', '4']])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::where(['city_id~=' => [1, 4]])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::where(['city_id~=' => ['1', '4']])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::where(['city_id' => []])->fetch();
        $this->assertCount(0, $documents);

        $documents = City::where(['city_id' => ['$ne' => 10]])->fetch();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);
    }

    public function test_whereRaw()
    {
        $documents = City::query()->whereRaw(['city_id' => ['$lt' => 10]])->fetch();
        $this->assertCount(9, $documents);
    }

    public function test_whereBetween()
    {
        $documents = City::query()->whereBetween('city_id', 2, 3)->fetch();
        $this->assertCount(2, $documents);

        $documents = City::query()->whereBetween('city_id', '2', '3')->fetch();
        $this->assertCount(2, $documents);

        $documents = City::query()->whereBetween('city_id', 2, 2)->fetch();
        $this->assertCount(1, $documents);
    }

    public function test_whereNotBetween()
    {
        $documents = City::query()->whereNotBetween('city_id', 100, 600)->fetch();
        $this->assertCount(99, $documents);

        $documents = City::query()->whereNotBetween('city_id', '100', '600')->fetch();
        $this->assertCount(99, $documents);

        $documents = City::query()->whereNotBetween('city_id', 50, 200)->whereNotBetween('city_id', 200, 600)->fetch();
        $this->assertCount(49, $documents);
    }

    public function test_whereIn()
    {
        $documents = City::query()->whereIn('city_id', [1, 2, 3, 4])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::query()->whereIn('city_id', ['1', '2', '3', '4'])->fetch();
        $this->assertCount(4, $documents);

        $documents = City::query()->whereIn('city_id', [1, 2, 3, 4])->whereIn('city_id', [2, 4])->fetch();
        $this->assertCount(2, $documents);

        $documents = City::query()->whereIn('city_id', ['1', '2', '3', '4'])->whereIn('city_id', ['2', '4'])->fetch();
        $this->assertCount(2, $documents);

        $documents = City::query()->whereIn('city_id', [])->fetch();
        $this->assertCount(0, $documents);
    }

    public function test_whereNotIn()
    {
        $documents = City::query()->whereNotIn('city_id', [1, 2, 3, 4])->fetch();
        $this->assertCount(596, $documents);

        $documents = City::query()->whereNotIn('city_id', ['1', '2', '3', '4'])->fetch();
        $this->assertCount(596, $documents);

        $documents = City::query()->whereNotIn('city_id', [])->fetch();
        $this->assertCount(600, $documents);
    }

    public function test_whereContains()
    {
        $documents = Address::query()->whereContains('address', 'as')->fetch();
        $this->assertCount(24, $documents);
        $documents = Address::query()->whereContains('district', 'as')->fetch();
        $this->assertCount(48, $documents);

        $documents = Address::query()->whereContains(['address', 'district'], 'as')->fetch();
        $this->assertCount(71, $documents);
    }

    public function test_whereNotContains()
    {
        $documents = Address::query()->whereNotContains('address', 'as')->fetch();
        $this->assertCount(579, $documents);
        $documents = Address::query()->whereNotContains('district', 'as')->fetch();
        $this->assertCount(555, $documents);

        $documents = Address::query()->whereNotContains(['address', 'district'], 'as')->fetch();
        $this->assertCount(532, $documents);
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
        $this->assertEquals(450, City::query()->whereLike('city', '%a%')->count());
        $this->assertEquals(4, City::query()->whereLike('city', 'A___')->count());
        $this->assertEquals(83, City::query()->whereLike('city', '%a___')->count());
    }

    public function test_whereNotLike()
    {
        $this->assertEquals(600, City::query()->whereNotLike('city', 'a')->count());
        $this->assertEquals(557, City::query()->whereNotLike('city', 'A%')->count());
        $this->assertEquals(475, City::query()->whereNotLike('city', '%a')->count());
        $this->assertEquals(150, City::query()->whereNotLike('city', '%a%')->count());
        $this->assertEquals(596, City::query()->whereNotLike('city', 'A___')->count());
        $this->assertEquals(517, City::query()->whereNotLike('city', '%a___')->count());
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
        $documents = City::query()->orderBy('city_id')->limit(10, 100)->fetch();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::query()->orderBy('city_id asc')->limit(10, 100)->fetch();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::query()->orderBy('city_id desc')->limit(10, 100)->fetch();
        $this->assertEquals(500, $documents[0]->city_id);

        $documents = City::query()->orderBy('country_id desc, city_id desc')->limit(10, 100)->fetch();
        $this->assertEquals(526, $documents[0]->city_id);

        $documents = City::query()->orderBy(['city_id' => SORT_ASC])->limit(10, 100)->fetch();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::query()->orderBy(['city_id' => SORT_DESC])->limit(10, 100)->fetch();
        $this->assertEquals(500, $documents[0]->city_id);

        $documents = City::query()->orderBy(['city_id' => 'desc'])->limit(10, 100)->fetch();
        $this->assertEquals(500, $documents[0]->city_id);
    }

    public function test_limit()
    {
        $documents = City::query()->limit(10)->fetch();
        $this->assertCount(10, $documents);
    }

    public function test_page()
    {
        $documents = City::query()->page(10, 2)->fetch();
        $this->assertCount(10, $documents);
        $this->assertEquals(11, $documents[0]->city_id);
    }

//    public function test_groupBy(){
//        $documents=City::createCriteria()->groupBy('country_id')->execute();
//        $this->assertEquals([],$documents);
//    }

    public function test_groupBy()
    {
        $documents = City::query()->groupBy('country_id')->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertCount(109, $documents);

        $documents = City::query()->groupBy('country_id, city_id')->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertCount(600, $documents);

        $documents = City::query()->where(['country_id' => 24])->groupBy('country_id')->aggregate(
            ['sum' => 'SUM(city_id)']
        );
        $this->assertCount(1, $documents);

        $documents = City::query()->groupBy(['city' => ['$substr' => ['$city', 0, 1]]])->orderBy('count')->aggregate(
            ['count' => 'COUNT(*)']
        );
        $this->assertCount(30, $documents);

        $documents = City::query()->groupBy('substr(city, 1, 1)')->orderBy('count')->aggregate(['count' => 'COUNT(*)']);
        $this->assertCount(30, $documents);

        $documents = City::query()->groupBy('substr(city, 1, 1)')->orderBy('count')->indexBy('city')->aggregate(
            ['count' => 'COUNT(*)']
        );
        $this->assertCount(30, $documents);
        $this->assertArrayHasKey('s', $documents);
    }

    public function test_indexBy()
    {
        $cities = City::query()->indexBy('city_id')->limit(1)->fetch();
        $this->assertArrayHasKey(1, $cities);
        $cities = City::query()->indexBy(['city_id' => 'city'])->limit(1)->execute();
        $this->assertEquals([1 => 'A Corua (La Corua)'], $cities);

        $cities = City::query()->indexBy(
            function ($row) {
                return 'city_id_' . $row['city_id'];
            }
        )->limit(1)->execute();
        $this->assertArrayHasKey('city_id_1', $cities);

        $this->assertArrayHasKey(
            's', City::query()->groupBy('substr(city, 1, 1)')->indexBy('city')->aggregate(['count' => 'COUNT(*)'])
        );
        $this->assertArrayHasKey(600, City::query()->indexBy('city_id')->fetch());
    }

    public function test_count()
    {
        $this->assertEquals(600, City::query()->count());
        $this->assertEquals(3, City::query()->where(['country_id' => 2])->count());
    }
}