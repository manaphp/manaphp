<?php

/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/12
 * Time: 17:07
 */

namespace Tests;

use ManaPHP\Data\Db;
use ManaPHP\Data\Db\Model;
use ManaPHP\Data\Db\SqlFragment\Increment;
use ManaPHP\Data\DbInterface;
use ManaPHP\Exception;
use ManaPHP\Identity\Adapter\Jwt;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;
use Tests\Models\Actor;
use Tests\Models\City;
use Tests\Models\Payment;
use Tests\Models\Student;
use Tests\Models\StudentShardDb;
use Tests\Models\StudentShardTable;

class TestCity1 extends Model
{

}

class TestCity2 extends Model
{
    public function table($context = null)
    {
        return 'city';
    }
}

class TestCity3 extends Model
{
    public function table($context = null)
    {
        return 'the_city';
    }
}

class DbModelTest extends TestCase
{
    /**
     * @var \ManaPHP\Data\Db\ConnectionInterface
     */
    public $connection;

    public function setUp()
    {
        $di = new Factory();
        $di->alias->set('@data', __DIR__ . '/tmp/data');

        $config = require __DIR__ . '/config.database.php';
        $this->connection = new Db\Connection\Adapter\Mysql($config['mysql']);
        $db = new Db($this->connection);
        $di->set('db', $db);
        $di->set('identity', new Jwt(['key' => 'test']));
        $db->attachEvent(
            'db:beforeQuery', function (DbInterface $source) {
            // var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getEmulatedSQL());
        }
        );
        $di->identity->setClaims([]);
    }

    public function test_count()
    {
        $this->assertInternalType('int', Actor::count());

        $this->assertEquals(200, Actor::count());

        $this->assertEquals(1, Actor::count(['actor_id' => 1]));

        $this->assertEquals(128, Actor::count([], ' DISTINCT first_name'));
    }

    public function test_where1v1()
    {
        $this->assertEquals(2, City::count(['city_id,country_id' => '1']));
        $this->assertEquals(1, City::count(['city_id,country_id' => '1,87']));
        $this->assertEquals(1, City::count(['city_id,country_id' => '87,1']));
        $this->assertEquals(0, City::count(['city_id,country_id' => '1,1']));
    }

    public function test_sum()
    {
        $sum = Payment::sum('amount');
        $this->assertEquals('string', gettype($sum));
        $this->assertEquals(67417.0, round($sum, 0));

        $sum = Payment::sum('amount', ['customer_id' => 1]);
        $this->assertEquals('118.68', $sum);
    }

    public function test_max()
    {
        $max = Payment::max('amount');
        $this->assertEquals('string', gettype($max));
        $this->assertEquals('11.99', $max);
    }

    public function test_min()
    {
        $min = Payment::min('amount');
        $this->assertEquals('string', gettype($min));
        $this->assertEquals('0.00', $min);
    }

    public function test_avg()
    {
        $avg = Payment::avg('amount');
        $this->assertEquals('double', gettype($avg));

        $this->assertEquals(4.20, round($avg, 2));
    }

    public function test_first()
    {
        $actor = Actor::first([]);
        $this->assertTrue(is_object($actor));
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertInstanceOf('ManaPHP\Data\Db\Model', $actor);

        $this->assertTrue(is_object(Actor::first(['actor_id' => '1'])));

        $actor = Actor::first(['actor_id' => 10]);
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertEquals('10', $actor->actor_id);

        $actor = Actor::first(['actor_id' => 5]);
        $this->assertEquals(5, $actor->actor_id);

        $actor = Actor::first(['actor_id' => 5, 'first_name' => 'JOHNNY']);
        $this->assertEquals(5, $actor->actor_id);
    }

    public function test_exists()
    {
        $this->assertFalse(Actor::exists(-1));
        $this->assertTrue(Actor::exists(1));
    }

    public function test_first_usage()
    {
        $this->assertEquals(10, City::first(['city_id' => 10])->city_id);
    }

    public function test_all()
    {
        $actors = Actor::all();
        $this->assertInternalType('array', $actors);
        $this->assertCount(200, $actors);
        $this->assertInstanceOf(get_class(new Actor()), $actors[0]);
        $this->assertInstanceOf('ManaPHP\Data\Db\Model', $actors[0]);

        $this->assertCount(200, Actor::all([]));

        $this->assertCount(0, Actor::all(['actor_id' => -1]));
        $this->assertEquals([], Actor::all(['actor_id' => -1]));

        $cities = City::all(['country_id' => 2], ['order' => 'city desc']);
        $this->assertCount(3, $cities);
        $this->assertEquals(483, $cities[0]->city_id);

        $this->assertCount(6, City::all(['city_id%=' => [100, 1]]));
        $this->assertCount(11, City::all(['city_id~=' => [10, 20]]));
    }

    public function test_lists()
    {
        $this->assertCount(600, City::lists('city'));
        $this->assertCount(3, City::kvalues('city', ['country_id' => 2]));
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
     * @param Model $model
     */
    protected function truncateTable($model)
    {
        $this->connection->truncate($model->table());
    }

    public function test_create()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertEquals(1, $student->id);

        $student = Student::get(1);
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
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $student = Student::get(1);
        $student->age = 22;
        $student->name = 'mana2';
        $student->update();

        $student = Student::get(1);
        $this->assertEquals(1, $student->id);
        $this->assertEquals(22, $student->age);
        $this->assertEquals('mana2', $student->name);

        $student->update();
    }

    public function test_updateAll()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $student = new Student();
        $student->age = 22;
        $student->name = 'mana2';
        $student->create();

        $this->assertEquals(2, Student::updateAll(['name' => 'm'], ['id>' => 0]));

        $student->update();
    }

    public function test_save()
    {
        $this->truncateTable(new Student());

        $student = new Student();

        $student->id = 1;
        $student->age = 30;
        $student->name = 'manaphp';
        $student->save();

        $student = Student::get(1);
        $this->assertInstanceOf(Student::class, $student);
        $this->assertEquals('1', $student->id);
        $this->assertEquals('30', $student->age);
        $this->assertEquals('manaphp', $student->name);

        $student->delete();

//        $student = new Student();
//        $student->create(['id' => 1, 'age' => 32, 'name' => 'beijing']);
//        $student = Student::findFirst(1);
//        $this->assertTrue($student instanceof Student);
//        $this->assertEquals('1', $student->id);
//        $this->assertEquals('32', $student->age);
//        $this->assertEquals('beijing', $student->name);
    }

    public function test_delete()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertNotFalse(Student::get(1));
        $student->delete();
        $this->assertNull(Student::get(1));
    }

    public function test_deleteAll()
    {
        $this->truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertNotFalse(Student::get(1));

        Student::deleteAll(['id>' => 0]);
        $this->assertNull(Student::get(1));
    }

    public function test_assign()
    {
        //normal usage
        $template = new  City();

        $template->city_id = 1;
        $template->city = 'beijing';

        $city = new City();
        $city->assign($template, ['city_id', 'city']);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);

        $city = new City();
        $city->assign($template->toArray(), ['city_id', 'city']);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);
    }

    public function test_getTable()
    {
        //infer the table name from table name
        $city = new TestCity1();
        $this->assertEquals('test_city1', $city->table());

        //use getSource
        $city = new TestCity2();
        $this->assertEquals('city', $city->table());

        //use setSource
        $city = new TestCity3();
        $this->assertEquals('the_city', $city->table());
    }

    public function test_getSnapshotData()
    {
        $actor = Actor::get(1);
        $snapshot = $actor->getSnapshotData();

        $this->assertEquals($snapshot, $actor->toArray());
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

    public function test_assignment()
    {
        $payment = Payment::get(1);
        $this->assertEquals(2.99, round($payment->amount, 2));

        $payment->amount = new Increment(0.01, '+');
        $payment->save();
        $this->assertEquals(3, round(Payment::get(1)->amount, 2));

        $payment = Payment::get(1);
        $payment->amount = new Increment(0.01, '-');
        $payment->save();
        $this->assertEquals(2.99, round(Payment::get(1)->amount, 2));
    }

//    public function test_findFirstBy()
//    {
//        $actor = Actor::findFirstByActorId(2);
//        $this->assertInstanceOf(Actor::class, $actor);
//        $this->assertEquals(2, $actor->actor_id);
//    }
//
//    public function test_findBy()
//    {
//        $actors = Actor::findByFirstName('BEN');
//        $this->assertCount(2, $actors);
//    }
//
//    public function test_countBy()
//    {
//        $this->assertEquals(2, Actor::countByFirstName('BEN'));
//    }

    public function test_shardDb()
    {
        $student = new StudentShardDb();
        $student->id = 10;
        try {
            $student->create();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        $student = new StudentShardDb();
        $student->id = 10;
        try {
            $student->delete();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        $student = new StudentShardDb();
        $student->id = 10;
        $student->name = 'manaphp';
        try {
            $student->update();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            StudentShardDb::updateAll(['name' => 'mark'], ['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            StudentShardDb::deleteAll(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            StudentShardDb::all(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }
    }

    public function test_shardTable()
    {
        $student = new StudentShardTable();
        $student->id = 10;
        try {
            $student->create();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        $student = new StudentShardTable();
        $student->id = 10;
        try {
            $student->delete();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        $student = new StudentShardTable();
        $student->id = 10;
        $student->name = 'manaphp';
        try {
            $student->update();
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            StudentShardTable::updateAll(['name' => 'mark'], ['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            StudentShardTable::deleteAll(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            StudentShardTable::all(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }
    }
}
