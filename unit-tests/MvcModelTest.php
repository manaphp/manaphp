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

class MvcModelTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $di;

    public function setUp()
    {
        $this->di = new ManaPHP\Di();

        $this->di->set('modelsManager', function () {
            return new ManaPHP\Mvc\Model\Manager();
        });

        $this->di->set('modelsMetadata', function () {
            return new ManaPHP\Mvc\Model\MetaData\Memory();
        });

        $this->di->set('db', function () {
            $config = require 'config.database.php';
            return new ManaPHP\Db\Adapter\Mysql($config['mysql']);
        });

        $this->di->getShared('db')
            ->attachEvent('db:beforeQuery', function ($event, \ManaPHP\DbInterface $source, $data) {
                //var_dump(['sql'=>$source->getSQLStatement(),'bind'=>$source->getSQLBindParams(),'bindTypes'=>$source->getSQLBindTypes()]);
                //      var_dump($source->getSQLStatement());
            });
    }

    public function test_count()
    {
        $this->assertTrue(is_int(Actor::count()));

        $this->assertEquals(200, Actor::count());

        $this->assertEquals(1, Actor::count(['actor_id' => 1]));
        $this->assertEquals(1, Actor::count('actor_id=1'));
        $this->assertEquals(1, Actor::count(['actor_id=1']));
        $this->assertEquals(1, Actor::count(['conditions' => 'actor_id=1']));
        $this->assertEquals(0, Actor::count(['actor_id=0']));

        $this->assertEquals(128, Actor::count(null, 'DISTINCT first_name'));
    }

    public function test_sum()
    {
        $sum = Payment::sum('amount');
        $this->assertEquals('string', gettype($sum));
        $this->assertEquals(67417.0, round($sum, 0));
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

    public function test_average()
    {
        $avg = Payment::average('amount');
        $this->assertEquals('double', gettype($avg));

        $this->assertEquals(4.20, round($avg, 2));
    }

    public function test_findFirst()
    {
        $actor = Actor::findFirst();
        $this->assertTrue(is_object($actor));
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertInstanceOf('ManaPHP\Mvc\Model', $actor);

        $this->assertTrue(is_object(Actor::findFirst('')));
        $this->assertTrue(is_object(Actor::findFirst('actor_id=1')));
        $this->assertTrue(is_object(Actor::findFirst(['actor_id=1'])));
        $this->assertTrue(is_object(Actor::findFirst(['conditions' => 'actor_id=1'])));

        $actor = Actor::findFirst(['conditions' => 'first_name=\'BEN\'', 'order' => 'actor_id']);
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertEquals('83', $actor->actor_id);
        $this->assertEquals('WILLIS', $actor->last_name);

        $actor2 = Actor::findFirst([
            'conditions' => 'first_name=:first_name',
            'bind' => ['first_name' => 'BEN'],
            'order' => 'actor_id'
        ]);
        $this->assertInstanceOf(get_class(new Actor()), $actor2);
        $this->assertEquals($actor->actor_id, $actor2->actor_id);

        $actor = Actor::findFirst(10);
        $this->assertInstanceOf(get_class(new Actor()), $actor);
        $this->assertEquals('10', $actor->actor_id);

        $actor = Actor::findFirst(['actor_id' => 5]);
        $this->assertEquals(5, $actor->actor_id);

        $actor = Actor::findFirst(['actor_id' => 5, 'first_name' => 'JOHNNY']);
        $this->assertEquals(5, $actor->actor_id);
    }

    public function test_findFirst_usage()
    {
        $this->assertEquals(10, City::findFirst(10)->city_id);
        $this->assertEquals(10, City::findFirst(['city_id' => 10])->city_id);
        $this->assertEquals(10, City::findFirst(['conditions' => ['city_id' => 10]])->city_id);
        $this->assertEquals(10, City::findFirst(['conditions' => 'city_id =:city_id', 'bind' => ['city_id' => 10]])->city_id);
    }

    public function test_find()
    {
        $actors = Actor::find();
        $this->assertTrue(is_array($actors));
        $this->assertCount(200, $actors);
        $this->assertInstanceOf(get_class(new Actor()), $actors[0]);
        $this->assertInstanceOf('ManaPHP\Mvc\Model', $actors[0]);

        $this->assertCount(200, Actor::find());
        $this->assertCount(200, Actor::find(''));
        $this->assertCount(200, Actor::find([]));
        $this->assertCount(200, Actor::find(['']));

        $this->assertCount(2, Actor::find('first_name=\'BEN\''));
        $this->assertCount(2, Actor::find([
            'first_name=:first_name',
            'bind' => ['first_name' => 'BEN']
        ]));

        $this->assertCount(1, Actor::find([
            'first_name=:first_name',
            'bind' => ['first_name' => 'BEN'],
            'limit' => 1
        ]));

        $this->assertCount(0, Actor::find('actor_id =-1'));
        $this->assertEquals([], Actor::find('actor_id =-1'));

        //   $this->assertCount(1,Actor::find(['first_name'=>'BEN']));
    }

    public function test_find_usage()
    {
        $this->assertCount(3, City::find(['country_id' => 2]));
        $this->assertCount(3, City::find(['conditions' => ['country_id' => 2], 'order' => 'city_id desc']));
        $this->assertCount(3, City::find([['country_id' => 2], 'order' => 'city_id desc']));
        $this->assertCount(3, City::find(['conditions' => 'country_id =:country_id', 'bind' => ['country_id' => 2]]));
        $this->assertCount(2, City::find([['country_id' => 2], 'limit' => 2]));
        $this->assertCount(1, City::find([['country_id' => 2], 'limit' => 1, 'offset' => 2]));
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
        $db->execute('TRUNCATE TABLE ' . $model->getSource());
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
    }

    public function test_save()
    {
        $this->_truncateTable(new Student());

        $student = new Student();

        $student->id = 1;
        $student->age = 30;
        $student->name = 'manaphp';
        $this->assertTrue($student->save());

        $student = Student::findFirst(1);
        $this->assertNotEquals(false, $student);
        $this->assertTrue($student instanceof Student);
        $this->assertEquals('1', $student->id);
        $this->assertEquals('30', $student->age);
        $this->assertEquals('manaphp', $student->name);
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

    public function test_assign()
    {
        //normal usage
        $city = new City();
        $city->assign(['city_id' => 1, 'city' => 'beijing']);
        $this->assertEquals(1, $city->city_id);
        $this->assertEquals('beijing', $city->city);

        //normal usage with whitelist
        $city = new City();
        $city->assign(['city_id' => 1, 'city' => 'beijing'], ['city_id']);
        $this->assertEquals(1, $city->city_id);
        $this->assertNull($city->city);
    }

}
