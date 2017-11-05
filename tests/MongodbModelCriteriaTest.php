<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mongodb;
use PHPUnit\Framework\TestCase;
use Tests\Mongodb\Models\Address;
use Tests\Mongodb\Models\City;

class MongodbModelCriteriaTest extends TestCase
{
    public function setUp()
    {
        $di = new FactoryDefault();

        $config = require __DIR__ . '/config.database.php';
        $di->setShared('mongodb', new Mongodb($config['mongodb']));
    }

    public function test_construct()
    {
        $document = City::criteria()->fetchOne();
        $this->assertEquals(['_id', 'city_id', 'city', 'country_id', 'last_update'], array_keys($document->toArray()));

        $document = City::criteria(['city_id', 'city'])->fetchOne();
        $this->assertEquals(['_id', 'city_id', 'city'], array_keys(array_filter($document->toArray())));
    }

    public function test_distinctField()
    {
        $this->assertCount(600, City::criteria()->distinctField('city_id'));
        $this->assertCount(3, City::criteria()->where('country_id', 2)->distinctField('city_id'));
    }

    public function test_select()
    {
        $documents = City::criteria()->limit(1)->fetchAll();
        $this->assertCount(1, $documents);
        $this->assertEquals(['_id', 'city_id', 'city', 'country_id', 'last_update'], array_keys($documents[0]->toArray()));

        $documents = City::criteria()->select('_id, city_id, city')->limit(1)->fetchAll();
        $this->assertCount(1, $documents);
        $this->assertEquals(['_id', 'city_id', 'city'], array_keys(array_filter($documents[0]->toArray())));

        $documents = City::criteria()->select(['_id', 'city_id', 'city'])->limit(1)->fetchAll();
        $this->assertCount(1, $documents);
        $this->assertEquals(['_id', 'city_id', 'city'], array_keys(array_filter($documents[0]->toArray())));
    }

    public function test_aggregate()
    {
        $documents = City::criteria()->aggregate(['max' => 'MAX(city_id)']);
        $this->assertEquals(600, $documents[0]['max']);

        $documents = City::criteria()->aggregate(['min' => 'MIN(city_id)']);
        $this->assertEquals(1, $documents[0]['min']);

        $documents = City::criteria()->aggregate(['avg' => 'AVG(city_id)']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::criteria()->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertEquals(180300, $documents[0]['sum']);

        $documents = City::criteria()->aggregate(['avg' => 'AVG(city_id)']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::criteria()->aggregate(['sum' => 'SUM(city_id)', 'avg' => 'AVG(city_id)']);
        $this->assertEquals(180300, $documents[0]['sum']);
        $this->assertEquals(300.5, $documents[0]['avg']);

        $documents = City::criteria()->aggregate(['max' => 'MAX(city_id+country_id)']);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::criteria()->aggregate(['max' => 'MAX(city_id + country_id)']);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::criteria()->aggregate(['max' => ['$max' => ['$add' => ['$city_id', '$country_id']]]]);
        $this->assertEquals(691, $documents[0]['max']);

        $documents = City::criteria()->aggregate(['sum' => 'sum(city_id*country_id)']);
        $this->assertEquals(10003864, $documents[0]['sum']);

        $documents = City::criteria()->aggregate(['sum' => 'SUM(city_id*2.5)']);
        $this->assertEquals(450750, $documents[0]['sum']);
    }

    public function test_where()
    {
        /**
         * @var City $document
         */
        $document = City::criteria()->where('city_id', 2)->fetchOne();
        $this->assertEquals(2, $document->city_id);

        $document = City::criteria()->where('city_id', '2')->fetchOne();
        $this->assertEquals(2, $document->city_id);

        $document = City::criteria()->where(['city_id' => 2])->fetchOne();
        $this->assertEquals(2, $document->city_id);

        $documents = City::criteria()->where('city_id', -2)->fetchAll();
        $this->assertEmpty($documents);

        $documents = City::criteria()->where('city_id=', 10)->fetchAll();
        $this->assertCount(1, $documents);
        $this->assertEquals(10, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id>', 10)->fetchAll();
        $this->assertCount(590, $documents);
        $this->assertEquals(11, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id>=', 10)->fetchAll();
        $this->assertCount(591, $documents);
        $this->assertEquals(10, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id<', 10)->fetchAll();
        $this->assertCount(9, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id<=', 10)->fetchAll();
        $this->assertCount(10, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id!=', 10)->fetchAll();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::criteria()->where('city_id<>', 10)->fetchAll();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);

        $documents = City::criteria()->where('city^=', 'Ab')->fetchAll();
        $this->assertCount(2, $documents);
        $this->assertEquals(2, $documents[0]->city_id);
        $this->assertEquals(3, $documents[1]->city_id);

        $documents = City::criteria()->where('city$=', 'a')->fetchAll();
        $this->assertCount(125, $documents);

        $documents = City::criteria()->where('city*=', 'a')->fetchAll();
        $this->assertCount(435, $documents);

        $documents = City::criteria()->where('city~=', 'a')->fetchAll();
        $this->assertCount(450, $documents);

        $documents = City::criteria()->where('city_id', [1, 2, 3, 4])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->where('city_id', ['1', '2', '3', '4'])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->where('city_id~=', [1, 4])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->where('city_id~=', ['1', '4'])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->where('city_id', [])->fetchAll();
        $this->assertCount(0, $documents);

        $documents = City::criteria()->where('city_id', ['$ne' => 10])->fetchAll();
        $this->assertCount(599, $documents);
        $this->assertEquals(1, $documents[0]->city_id);
    }

    public function test_whereRaw()
    {
        $documents = City::criteria()->whereRaw(['city_id' => ['$lt' => 10]])->fetchAll();
        $this->assertCount(9, $documents);
    }

    public function test_whereBetween()
    {
        $documents = City::criteria()->whereBetween('city_id', 2, 3)->fetchAll();
        $this->assertCount(2, $documents);

        $documents = City::criteria()->whereBetween('city_id', '2', '3')->fetchAll();
        $this->assertCount(2, $documents);

        $documents = City::criteria()->whereBetween('city_id', 2, 2)->fetchAll();
        $this->assertCount(1, $documents);
    }

    public function test_whereNotBetween()
    {
        $documents = City::criteria()->whereNotBetween('city_id', 100, 600)->fetchAll();
        $this->assertCount(99, $documents);

        $documents = City::criteria()->whereNotBetween('city_id', '100', '600')->fetchAll();
        $this->assertCount(99, $documents);

        $documents = City::criteria()->whereNotBetween('city_id', 50, 200)->whereNotBetween('city_id', 200, 600)->fetchAll();
        $this->assertCount(49, $documents);
    }

    public function test_whereIn()
    {
        $documents = City::criteria()->whereIn('city_id', [1, 2, 3, 4])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->whereIn('city_id', ['1', '2', '3', '4'])->fetchAll();
        $this->assertCount(4, $documents);

        $documents = City::criteria()->whereIn('city_id', [1, 2, 3, 4])->whereIn('city_id', [2, 4])->fetchAll();
        $this->assertCount(2, $documents);

        $documents = City::criteria()->whereIn('city_id', ['1', '2', '3', '4'])->whereIn('city_id', ['2', '4'])->fetchAll();
        $this->assertCount(2, $documents);

        $documents = City::criteria()->whereIn('city_id', [])->fetchAll();
        $this->assertCount(0, $documents);
    }

    public function test_whereNotIn()
    {
        $documents = City::criteria()->whereNotIn('city_id', [1, 2, 3, 4])->fetchAll();
        $this->assertCount(596, $documents);

        $documents = City::criteria()->whereNotIn('city_id', ['1', '2', '3', '4'])->fetchAll();
        $this->assertCount(596, $documents);

        $documents = City::criteria()->whereNotIn('city_id', [])->fetchAll();
        $this->assertCount(600, $documents);
    }

    public function test_whereContains()
    {
        $documents = Address::criteria()->whereContains('address', 'as')->fetchAll();
        $this->assertCount(21, $documents);
        $documents = Address::criteria()->whereContains('district', 'as')->fetchAll();
        $this->assertCount(44, $documents);

        $documents = Address::criteria()->whereContains(['address', 'district'], 'as')->fetchAll();
        $this->assertCount(64, $documents);
    }

    public function test_whereStartsWith()
    {
        $this->assertEquals(38, City::criteria()->whereStartsWith('city', 'A')->count());
        $this->assertEquals(4, City::criteria()->whereStartsWith('city', 'A', 4)->count());
    }

    public function test_whereEndsWith()
    {
        $this->assertEquals(125, City::criteria()->whereEndsWith('city', 'a')->count());
    }

    public function test_whereLike()
    {
        $this->assertEquals(0, City::criteria()->whereLike('city', 'a')->count());
        $this->assertEquals(38, City::criteria()->whereLike('city', 'A%')->count());
        $this->assertEquals(125, City::criteria()->whereLike('city', '%a')->count());
        $this->assertEquals(435, City::criteria()->whereLike('city', '%a%')->count());
        $this->assertEquals(4, City::criteria()->whereLike('city', 'A___')->count());
        $this->assertEquals(76, City::criteria()->whereLike('city', '%a___')->count());
    }

    public function test_whereRegex()
    {
        $this->assertEquals(46, City::criteria()->whereRegex('city', 'A')->count());
        $this->assertEquals(125, City::criteria()->whereRegex('city', 'a$')->count());
        $this->assertEquals(38, City::criteria()->whereRegex('city', '^A')->count());
        $this->assertEquals(262, City::criteria()->whereRegex('city', 'a....')->count());
        $this->assertEquals(34, City::criteria()->whereRegex('city', '^A....')->count());
    }

    public function test_whereNull()
    {
        $this->assertEquals(0, City::criteria()->whereNull('city_id')->count());
    }

    public function test_whereNotNull()
    {
        $this->assertEquals(600, City::criteria()->whereNotNull('city_id')->count());
    }

    public function test_orderBy()
    {
        $documents = City::criteria()->orderBy('city_id')->limit(10, 100)->fetchAll();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::criteria()->orderBy('city_id asc')->limit(10, 100)->fetchAll();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::criteria()->orderBy('city_id desc')->limit(10, 100)->fetchAll();
        $this->assertEquals(500, $documents[0]->city_id);

        $documents = City::criteria()->orderBy('country_id desc, city_id desc')->limit(10, 100)->fetchAll();
        $this->assertEquals(526, $documents[0]->city_id);

        $documents = City::criteria()->orderBy(['city_id' => SORT_ASC])->limit(10, 100)->fetchAll();
        $this->assertEquals(101, $documents[0]->city_id);

        $documents = City::criteria()->orderBy(['city_id' => SORT_DESC])->limit(10, 100)->fetchAll();
        $this->assertEquals(500, $documents[0]->city_id);

        $documents = City::criteria()->orderBy(['city_id' => 'desc'])->limit(10, 100)->fetchAll();
        $this->assertEquals(500, $documents[0]->city_id);
    }

    public function test_limit()
    {
        $documents = City::criteria()->limit(10)->fetchAll();
        $this->assertCount(10, $documents);
    }

    public function test_page()
    {
        $documents = City::criteria()->page(10, 2)->fetchAll();
        $this->assertCount(10, $documents);
        $this->assertEquals(11, $documents[0]->city_id);
    }

//    public function test_groupBy(){
//        $documents=City::createCriteria()->groupBy('country_id')->execute();
//        $this->assertEquals([],$documents);
//    }

    public function test_groupBy()
    {
        $documents = City::criteria()->groupBy('country_id')->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertCount(109, $documents);

        $documents = City::criteria()->groupBy('country_id, city_id')->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertCount(600, $documents);

        $documents = City::criteria()->where(['country_id' => 24])->groupBy('country_id')->aggregate(['sum' => 'SUM(city_id)']);
        $this->assertCount(1, $documents);

        $documents = City::criteria()->groupBy(['city' => ['$substr' => ['$city', 0, 1]]])->orderBy('count')->aggregate(['count' => 'COUNT(*)']);
        $this->assertCount(30, $documents);

        $documents = City::criteria()->groupBy('substr(city, 1, 1)')->orderBy('count')->aggregate(['count' => 'COUNT(*)']);
        $this->assertCount(30, $documents);

        $documents = City::criteria()->groupBy('substr(city, 1, 1)')->orderBy('count')->indexBy('city')->aggregate(['count' => 'COUNT(*)']);
        $this->assertCount(30, $documents);
        $this->assertArrayHasKey('s', $documents);
    }

    public function test_indexBy()
    {
        $this->assertArrayHasKey('s', City::criteria()->groupBy('substr(city, 1, 1)')->indexBy('city')->aggregate(['count' => 'COUNT(*)']));
        $this->assertArrayHasKey(600, City::criteria()->indexBy('city_id')->fetchAll());
    }

    public function test_count()
    {
        $this->assertEquals(600, City::criteria()->count());
        $this->assertEquals(3, City::criteria()->where('country_id', 2)->count());
    }
}