<?php

namespace Tests;

use ManaPHP\Data\Mongodb;
use ManaPHP\Mvc\Factory;
use MongoDB\BSON\ObjectID;
use PHPUnit\Framework\TestCase;
use Tests\Mongodb\Models\Actor;
use Tests\Mongodb\Models\City;
use Tests\Mongodb\Models\City1;
use Tests\Mongodb\Models\City2;
use Tests\Mongodb\Models\City3;
use Tests\Mongodb\Models\DataType;
use Tests\Mongodb\Models\Student;

class DataMongodbModelTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Factory();
        $this->container->identity->setClaims([]);
        $config = require __DIR__ . '/config.database.php';
        $this->container->set('mongodb', new Mongodb($config['mongodb']));
    }

    public function test_getConsistentValue()
    {
        $dt = new DataType();
        $this->assertSame('manaphp', $dt->normalizeValue('string', 'manaphp'));
        $this->assertSame('123', $dt->normalizeValue('string', 123));

        $this->assertSame(123, $dt->normalizeValue('integer', 123));
        $this->assertSame(123, $dt->normalizeValue('integer', '123'));

        $this->assertSame(1.23, $dt->normalizeValue('double', 1.23));
        $this->assertSame(1.23, $dt->normalizeValue('double', '1.23'));

        $objectId = new ObjectID();
        $this->assertEquals($objectId, $dt->normalizeValue('objectid', $objectId));

        $this->assertEquals(
            new ObjectID('123456789012345678901234'), $dt->normalizeValue('objectid', '123456789012345678901234')
        );

        $this->assertTrue($dt->normalizeValue('boolean', true));
        $this->assertTrue($dt->normalizeValue('boolean', 1));
        $this->assertFalse($dt->normalizeValue('boolean', 0));
    }

    public function test_count()
    {
        $this->assertInternalType('int', City::count());

        $this->assertEquals(600, City::count());
        $this->assertEquals(3, City::count(['country_id' => 2]));
    }

    public function test_sum()
    {
        $avg = City::sum('city_id');
        $this->assertEquals('integer', gettype($avg));
        $this->assertEquals(180300, round($avg, 2));

        $avg = City::sum('city_id', ['country_id' => 2]);
        $this->assertEquals('integer', gettype($avg));
        $this->assertEquals(605, round($avg, 2));
    }

    public function test_max()
    {
        $this->assertEquals(600, City::max('city_id'));
        $this->assertEquals(483, City::max('city_id', ['country_id' => 2]));
    }

    public function test_min()
    {
        $this->assertEquals(600, City::max('city_id'));
        $this->assertEquals(483, City::max('city_id', ['country_id' => 2]));
    }

    public function test_avg()
    {
        $avg = City::avg('city_id');
        $this->assertEquals('double', gettype($avg));
        $this->assertEquals(300.5, round($avg, 2));

        $avg = City::avg('city_id', ['country_id' => 1]);
        $this->assertEquals('double', gettype($avg));
        $this->assertEquals(251, round($avg, 2));
    }

    public function test_first()
    {
        $actor = Actor::first([]);
        $this->assertTrue(is_object($actor));
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertInstanceOf('ManaPHP\Data\Mongodb\Model', $actor);

        $this->assertTrue(is_object(Actor::first(['actor_id' => 1])));

        $actor = Actor::get(10);
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertEquals('10', $actor->actor_id);

        $actor = Actor::first(['actor_id' => 5]);
        $this->assertEquals(5, $actor->actor_id);

        $actor = Actor::first(['actor_id' => 5, 'first_name' => 'JOHNNY']);
        $this->assertEquals(5, $actor->actor_id);

        $this->assertNotFalse(City::first('10'));
    }

    public function test_exists()
    {
        $this->assertFalse(Actor::exists(['actor_id' => -1]));
        $this->assertTrue(Actor::exists(['actor_id' => 1]));

        $this->assertTrue(City::exists(['actor_id' => '1']));
    }

    public function test_first_usage()
    {
        $this->assertEquals(10, City::get(10)->city_id);
        $this->assertEquals(10, City::first(['city_id' => 10])->city_id);
    }

    public function test_all()
    {
        $actors = Actor::all();
        $this->assertTrue(is_array($actors));
        $this->assertCount(200, $actors);
        $this->assertInstanceOf(get_class(new Actor()), $actors[0]);
        $this->assertInstanceOf('ManaPHP\Data\Mongodb\Model', $actors[0]);

        $this->assertCount(200, Actor::all([]));

        $this->assertCount(0, Actor::all(['actor_id' => -1]));
        $this->assertEquals([], Actor::all(['actor_id' => -1]));

        $cities = City::all(['country_id' => 2], ['order' => 'city desc']);
        $this->assertCount(3, $cities);
        $this->assertEquals(483, $cities[0]->city_id);
    }

    public function test_values()
    {
        $cities = City::values('city', []);
        $this->assertCount(599, $cities);
    }

    public function test_all_usage()
    {
        $this->assertCount(3, City::all(['country_id' => 2]));
        $this->assertCount(3, City::all(['country_id' => 2], ['order' => 'city_id desc']));
        $this->assertCount(2, City::all(['country_id' => 2], ['limit' => 2]));
        $this->assertCount(1, City::all(['country_id' => 2], ['limit' => 1, 'offset' => 2]));
    }

    /**
     * @param \ManaPHP\Data\Mongodb\Model $model
     */
    protected function truncateTable($model)
    {
        /**
         * @var \ManaPHP\Data\Db $db
         */
        $db = $this->container->get('mongodb');
        $db->truncate($model->table());
    }

    public function test_create()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->id = 1;
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertEquals(1, $student->id);

        $student = Student::first(['id' => 1]);
        $this->assertEquals(1, $student->id);
        $this->assertEquals(21, $student->age);
        $this->assertEquals('mana', $student->name);

        //fixed bug: if record is existed already
        $student = new Student();
        $student->id = 2;
        $student->age = 21;
        $student->name = 'mana';
        $student->create();
    }

    public function test_update()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->id = 1;
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $student = Student::first(['id' => 1]);
        $student->age = 22;
        $student->name = 'mana2';
        $student->update();

        $student = Student::first(['id' => 1]);
        $this->assertEquals(1, $student->id);
        $this->assertEquals(22, $student->age);
        $this->assertEquals('mana2', $student->name);

        $student->update();
    }

    public function test_updateAll()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->id = 1;
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $student = new Student();
        $student->id = 2;
        $student->age = 22;
        $student->name = 'mana2';
        $student->create();

        $this->assertEquals(2, Student::updateAll(['name' => 'm'], []));

        $student->update();
    }

    public function test_deleteAll()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->id = 1;
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertNotNull(Student::first(['id' => 1]));

        Student::deleteAll([]);
        $this->assertNull(Student::first(['id' => 1]));
    }

    public function test_assign()
    {
        $template = new City();

        $template->city_id = 1;
        $template->city = 'beijing';

        $city = new City();
        $city->assign($template, ['city_id', 'city']);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);

        $city = new City();
        try {
            $city->assign($template->toArray(), ['city_id']);
            $this->assertFalse('why not1!');
        } catch (\Exception $e) {

        }
    }

    public function test_getSource()
    {
        //infer the table name from table name
        $city = new City1();
        $this->assertEquals('city1', $city->table());

        //use getSource
        $city = new City2();
        $this->assertEquals('city', $city->table());

        //use setSource
        $city = new City3();
        $this->assertEquals('the_city', $city->table());
    }

    public function test_getSnapshotData()
    {
        $actor = Actor::get(1);
        $snapshot = $actor->getSnapshotData();
        unset($snapshot['_id']);
        $this->assertSame($snapshot, $actor->toArray());
    }

    public function test_getChangedFields()
    {
        $actor = Actor::get(1);

        $actor->first_name = 'abc';
        $actor->last_name = 'mark';
        $this->assertEquals(['first_name', 'last_name'], $actor->getChangedFields());
    }

    public function test_hasChanged()
    {
        $actor = Actor::get(1);

        $actor->first_name = 'abc';
        $this->assertTrue($actor->hasChanged('first_name'));
        $this->assertTrue($actor->hasChanged(['first_name']));
    }
}
