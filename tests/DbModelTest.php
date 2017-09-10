<?php

/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/12
 * Time: 17:07
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

use Models\Actor;
use Models\City;
use Models\Payment;
use Models\Student;

class TestCity1 extends \ManaPHP\Mvc\Model
{

}

class TestCity2 extends \ManaPHP\Mvc\Model
{
    public static function getSource($context = null)
    {
        return 'city';
    }
}

class TestCity3 extends \ManaPHP\Mvc\Model
{
    public static function getSource($context = null)
    {
        return 'the_city';
    }
}

class DbModelTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $di;

    public function setUp()
    {
        $this->di = new \ManaPHP\Di\FactoryDefault();

        $this->di->set('db', function () {
            $config = require __DIR__ . '/config.database.php';
            //$db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
            $db = new ManaPHP\Db\Adapter\Proxy(['masters' => ['mysql://root@localhost:/manaphp_unit_test'], 'slaves' => ['mysql://root@localhost:/manaphp_unit_test']]);
            // $db= new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

            echo get_class($db), PHP_EOL;
            $db->attachEvent('db:beforeQuery', function (\ManaPHP\DbInterface $source) {
                // var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getEmulatedSQL());
            });

            return $db;
        });
    }

    public function test_count()
    {
        $this->assertTrue(is_int(Actor::count()));

        $this->assertEquals(200, Actor::count());

        $this->assertEquals(1, Actor::count(['actor_id' => 1]));

        $this->assertEquals(128, Actor::count([], ' DISTINCT first_name'));
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

    public function test_findFirst()
    {
        $actor = Actor::findFirst();
        $this->assertTrue(is_object($actor));
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertInstanceOf('ManaPHP\Mvc\Model', $actor);

        $this->assertTrue(is_object(Actor::findFirst(['actor_id' => '1'])));

        $actor = Actor::findFirst(10);
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertEquals('10', $actor->actor_id);

        $actor = Actor::findFirst(['actor_id' => 5]);
        $this->assertEquals(5, $actor->actor_id);

        $actor = Actor::findFirst(['actor_id' => 5, 'first_name' => 'JOHNNY']);
        $this->assertEquals(5, $actor->actor_id);
    }

    public function test_exists()
    {
        $this->assertFalse(Actor::exists(-1));
        $this->assertTrue(Actor::exists(1));
    }

    public function test_findFirst_usage()
    {
        $this->assertEquals(10, City::findFirst(10)->city_id);
        $this->assertEquals(10, City::findFirst(['city_id' => 10])->city_id);
    }

    public function test_find()
    {
        $actors = Actor::find();
        $this->assertTrue(is_array($actors));
        $this->assertCount(200, $actors);
        $this->assertInstanceOf(get_class(new Actor()), $actors[0]);
        $this->assertInstanceOf('ManaPHP\Mvc\Model', $actors[0]);

        $this->assertCount(200, Actor::find([]));

        $this->assertCount(0, Actor::find(['actor_id' => -1]));
        $this->assertEquals([], Actor::find(['actor_id' => -1]));

        $cities = City::find(['country_id' => 2], ['order' => 'city desc']);
        $this->assertCount(3, $cities);
        $this->assertEquals(483, $cities[0]->city_id);
    }

    public function test_findList()
    {
        $this->assertCount(600, City::findList());
        $this->assertCount(599, City::findList([], 'city'));
        $this->assertCount(3, City::findList(['country_id' => 2], 'city'));
        $this->assertCount(3, City::findList(['country_id' => 2], ['city_id' => 'city']));
    }

    public function test_findById()
    {
        $city = City::findById(10);
        $this->assertEquals(10, $city->city_id);
    }

    public function test_find_usage()
    {
        $this->assertCount(3, City::find(['country_id' => 2]));
        $this->assertCount(3, City::find(['country_id' => 2], ['order' => 'city_id desc']));
        $this->assertCount(2, City::find(['country_id' => 2], ['limit' => 2]));
        $this->assertCount(1, City::find(['country_id' => 2], ['limit' => 1, 'offset' => 2]));
    }

    /**
     * @param \ManaPHP\Mvc\Model $model
     */
    protected function _truncateTable($model)
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->di->getShared('db');
        $db->truncateTable($model->getSource());
    }

    public function test_create()
    {
        $this->_truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertEquals(1, $student->id);

        $student = Student::findFirst(1);
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
        $this->_truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $student = Student::findFirst(1);
        $student->age = 22;
        $student->name = 'mana2';
        $student->update();

        $student = Student::findFirst(1);
        $this->assertEquals(1, $student->id);
        $this->assertEquals(22, $student->age);
        $this->assertEquals('mana2', $student->name);

        $student->update();
    }

    public function test_updateAll()
    {
        $this->_truncateTable(new Student());

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
        $this->_truncateTable(new Student());

        $student = new Student();

        $student->id = 1;
        $student->age = 30;
        $student->name = 'manaphp';
        $student->save();

        $student = Student::findFirst(1);
        $this->assertTrue($student instanceof Student);
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
        $this->_truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertTrue(Student::findFirst(1) !== false);
        $student->delete();
        $this->assertTrue(Student::findFirst(1) === false);
    }

    public function test_deleteAll()
    {
        $this->_truncateTable(new Student());

        $student = new Student();
        $student->age = 21;
        $student->name = 'mana';
        $student->create();

        $this->assertTrue(Student::findFirst(1) !== false);

        Student::deleteAll(['id>' => 0]);
        $this->assertTrue(Student::findFirst(1) === false);
    }

    public function test_assign()
    {
        //normal usage
        $city = new City();
        $city->assign(['city_id' => 1, 'city' => 'beijing'], []);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);

        //normal usage with whitelist
        $city = new City();
        $city->assign(['city_id' => 1, 'city' => 'beijing'], ['city_id', 'city']);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);
    }

    public function test_getSource()
    {
        //infer the table name from table name
        $city = new TestCity1();
        $this->assertEquals('test_city1', $city->getSource());

        //use getSource
        $city = new TestCity2();
        $this->assertEquals('city', $city->getSource());

        //use setSource
        $city = new TestCity3();
        $this->assertEquals('the_city', $city->getSource());
    }

    public function test_getSnapshotData()
    {
        $actor = Actor::findFirst(1);
        $snapshot = $actor->getSnapshotData();

        $this->assertEquals($snapshot, $actor->toArray());
    }

    public function test_getChangedFields()
    {
        $actor = Actor::findFirst(1);

        $actor->first_name = 'abc';
        $actor->last_name = 'mark';
        $this->assertEquals(['first_name', 'last_name'], $actor->getChangedFields());
    }

    public function test_hasChanged()
    {
        $actor = Actor::findFirst(1);

        $actor->first_name = 'abc';
        $this->assertTrue($actor->hasChanged('first_name'));
        $this->assertTrue($actor->hasChanged(['first_name']));
    }

    public function test_assignment()
    {
        $payment = Payment::findFirst(1);
        $this->assertEquals(2.99, round($payment->amount, 2));

        $payment->amount = new \ManaPHP\Db\Assignment(0.01, '+');
        $payment->save();
        $this->assertEquals(3, round(Payment::findFirst(1)->amount, 2));

        $payment = Payment::findFirst(1);
        $payment->amount = new \ManaPHP\Db\Assignment(0.01, '-');
        $payment->save();
        $this->assertEquals(2.99, round(Payment::findFirst(1)->amount, 2));
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
        $student = new \Models\StudentShardDb();
        $student->id = 10;
        try {
            $student->create();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        $student = new \Models\StudentShardDb();
        $student->id = 10;
        try {
            $student->delete();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        };

        $student = new \Models\StudentShardDb();
        $student->id = 10;
        $student->name = 'manaphp';
        try {
            $student->update();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            \Models\StudentShardDb::updateAll(['name' => 'mark'], ['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            \Models\StudentShardDb::deleteAll(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }

        try {
            \Models\StudentShardDb::find(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('db_10', $e->getMessage());
        }
    }

    public function test_shardTable()
    {
        $student = new \Models\StudentShardTable();
        $student->id = 10;
        try {
            $student->create();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        $student = new \Models\StudentShardTable();
        $student->id = 10;
        try {
            $student->delete();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        $student = new \Models\StudentShardTable();
        $student->id = 10;
        $student->name = 'manaphp';
        try {
            $student->update();
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            \Models\StudentShardTable::updateAll(['name' => 'mark'], ['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            \Models\StudentShardTable::deleteAll(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }

        try {
            \Models\StudentShardTable::find(['id' => 10]);
            $this->assertFalse('why not?');
        } catch (\ManaPHP\Exception $e) {
            $this->assertContains('student_10', $e->getMessage());
        }
    }
}
