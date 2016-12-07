<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 20:13
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';
use Models\Address;
use Models\City;
use Models\Country;
use Models\Payment;

class MvcModelQueryBuilderTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $di;

    /**
     * @var \ManaPHP\Mvc\Model\Manager
     */
    protected $modelsManager;

    public function setUp()
    {
        $this->di = new ManaPHP\Di\FactoryDefault();

        $this->di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
            //   $db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

            $db->attachEvent('db:beforeQuery', function (ManaPHP\DbInterface $source) {
                var_dump($source->getSQL());
                var_dump($source->getEmulatedSQL());
            });

            echo get_class($db), PHP_EOL;
            return $db;
        });
        $this->modelsManager = $this->di->get('modelsManager');
    }

    public function test_distinct()
    {
        //select all implicitly
        $builder = $this->modelsManager->createBuilder()->columns('city_id')->addFrom(get_class(new Address()));
        $this->assertCount(603, $builder->execute());

        //select all explicitly
        $builder = $this->modelsManager->createBuilder()
            ->columns('city_id')
            ->addFrom(get_class(new Address()))
            ->distinct(false);
        $this->assertCount(603, $builder->execute());

        //select distinct
        $builder = $this->modelsManager->createBuilder()
            ->columns('city_id')
            ->addFrom(get_class(new Address()))
            ->distinct(true);
        $this->assertCount(599, $builder->execute());
    }

    public function test_columns()
    {
        //default select all columns
        $builder = $this->modelsManager->createBuilder()->addFrom(get_class(new Address()))->limit(2);
        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(8, $rows[0]);

        //select all columns explicitly
        $builder = $this->modelsManager->createBuilder()->columns('*')->addFrom(get_class(new Address()))->limit(2);
        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(8, $rows[0]);

        // select all columns explicitly and use table alias
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.*, c.*')
            ->addFrom(get_class(new Address()), 'a')
            ->leftJoin(get_class(new City()), 'c.city_id =a.city_id', 'c')
            ->limit(2);
        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(10, $rows[0]);

        //string format columns
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.address_id, a.address, a.phone')
            ->addFrom(get_class(new Address()), 'a')
            ->limit(2);
        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //dense multi space to only one for columns
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.address_id,
                        a.address,
                        c.city')
            ->addFrom(get_class(new Address()), 'a')
            ->leftJoin(get_class(new City()), 'c.city_id =a.city_id', 'c')
            ->limit(2)
            ->orderBy('a.address_id');
        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        $builder = $this->modelsManager->createBuilder()
            ->columns('count(address_id) as address_count')
            ->addFrom(get_class(new Address()));
        $rows = $builder->execute();
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]);
    }

    public function test_from()
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('address_id,address,phone')
            ->from(get_class(new Address()))
            ->limit(2);

        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);
    }

    public function test_addFrom()
    {
        //one model without alias
        $builder = $this->modelsManager->createBuilder()
            ->columns('address_id,address,phone')
            ->addFrom(get_class(new Address()))
            ->limit(2);

        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //one model with alias
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.address_id,a.address,a.phone')
            ->addFrom(get_class(new Address()), 'a')
            ->limit(2);

        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows[0]);

        //multi-models with alias
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.*, c.*')
            ->addFrom(get_class(new Address()), 'a')
            ->addFrom(get_class(new City()), 'c')
            ->limit(2);

        $rows = $builder->execute();
        $this->assertCount(2, $rows);
        $this->assertCount(10, $rows[0]);

        $builder = $this->modelsManager->createBuilder()
            ->columns('*')
            ->addFrom($this->modelsManager->createBuilder()
                ->columns('*')
                ->addFrom(get_class(new City()))
                ->where('city_id<=5'), 'cc');
        $rows = $builder->execute();
        $this->assertCount(5, $rows);
    }

    public function test_join()
    {
        if ($this->di->getShared('db') instanceof ManaPHP\Db\Adapter\Sqlite) {
            return;
        }

        //with model
        $builder = $this->modelsManager->createBuilder()
            ->columns('count(address_id) as address_count')
            ->addFrom(get_class(new Address()))
            ->join(get_class(new City()));
        $rows = $builder->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(361800, $rows[0]['address_count']);

        //with model,conditions
        $builder = $this->modelsManager->createBuilder()
            ->columns('count(address_id) as address_count')
            ->addFrom(get_class(new Address()), 'a')
            ->join(get_class(new City()), 'city.city_id =a.city_id');
        $rows = $builder->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(603, $rows[0]['address_count']);

        //with model,conditions,alias
        $builder = $this->modelsManager->createBuilder()
            ->columns('count(address_id) as address_count')
            ->addFrom(get_class(new Address()), 'a')
            ->join(get_class(new City()), 'c.city_id =a.city_id', 'c');
        $rows = $builder->execute();
        $this->assertCount(1, $rows);
        $this->assertEquals(603, $rows[0]['address_count']);

        //with model,conditions,alias,join
        $builder = $this->modelsManager->createBuilder()
            ->columns('a.address_id, a.address, a.city_id, c.city')
            ->addFrom(get_class(new Address()), 'a')
            ->join(get_class(new City()), 'c.city_id =a.city_id', 'c', 'LEFT');
        $rows = $builder->execute();
        $this->assertCount(603, $rows);

        $builder = $this->modelsManager->createBuilder()
            ->columns('a.address_id, a.address, a.city_id, c.city')
            ->addFrom(get_class(new Address()), 'a')
            ->join($this->modelsManager->createBuilder()
                ->columns('*')
                ->addFrom(get_class(new City()))
                ->where('city_id <:city_id', ['city_id' => 50]), 'c.city_id =a.city_id', 'c', 'RIGHT');
        $rows = $builder->execute();
        $this->assertCount(50, $rows);
    }

    public function test_innerJoin()
    {
        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $builder = $this->modelsManager->createBuilder()
            ->columns('c1.*,c2.*')
            ->addFrom(get_class(new City()), 'c1')
            ->innerJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCountry, $builder->execute());
    }

    public function test_leftJoin()
    {
        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $builder = $this->modelsManager->createBuilder()
            ->columns('c1.*,c2.*')
            ->addFrom(get_class(new City()), 'c1')
            ->leftJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCity, $builder->execute());
    }

    public function test_rightJoin()
    {
        if ($this->di->getShared('db') instanceof ManaPHP\Db\Adapter\Sqlite) {
            return;
        }

        $countCity = City::count();
        $this->assertEquals(600, $countCity);

        $countCountry = Country::count();
        $this->assertEquals(109, $countCountry);

        $builder = $this->modelsManager->createBuilder()
            ->columns('c1.*,c2.*')
            ->addFrom(get_class(new City()), 'c1')
            ->rightJoin(get_class(new Country()), 'c1.city_id=c2.country_id', 'c2');
        $this->assertCount($countCountry, $builder->execute());
    }

    public function test_where()
    {
        $builder = $this->modelsManager->createBuilder()->where('address_id <=100')->addFrom(get_class(new Address()));
        $this->assertCount(100, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->where('address_id <=:max_address_id', ['max_address_id' => 100])
            ->addFrom(get_class(new Address()));
        $this->assertCount(100, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->where('address_id', 100)
            ->addFrom(get_class(new Address()));
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->where('address_id >=:min_address_id AND address_id <=:max_address_id',
                ['min_address_id' => 51, 'max_address_id' => 100])
            ->addFrom(get_class(new Address()));
        $this->assertCount(50, $builder->execute());
    }

    public function test_andWhere()
    {
        $builder = $this->modelsManager->createBuilder()
            ->andWhere('address_id <=100')
            ->addFrom(get_class(new Address()));
        $this->assertCount(100, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->andWhere('address_id <=:max_address_id', ['max_address_id' => 100])
            ->addFrom(get_class(new Address()));
        $this->assertCount(100, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->andWhere('address_id >=:min_address_id', ['min_address_id' => 51])
            ->andWhere('address_id <=:max_address_id', ['max_address_id' => 100])
            ->addFrom(get_class(new Address()));
        $this->assertCount(50, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()))
            ->andWhere('address_id', 1);
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()))
            ->andWhere(' address_id ', 1);
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()))
            ->andWhere('address_id =', 1);
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()))
            ->andWhere('address_id <', 2);
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()))
            ->andWhere('address_id LIKE', '2%');
        $this->assertCount(110, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()), 'a')
            ->andWhere('a.address_id', 1);
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->addFrom(get_class(new Address()), 'a')
            ->andWhere('a.address_id', 1);
        $this->assertCount(1, $builder->execute());
    }

    public function test_betweenWhere()
    {
        $builder = $this->modelsManager->createBuilder()
            ->betweenWhere('address_id', 51, 100)
            ->addFrom(get_class(new Address()));
        $this->assertCount(50, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->betweenWhere('address_id', 51, 100)
            ->betweenWhere('address_id', 61, 70)
            ->addFrom(get_class(new Address()));
        $this->assertCount(10, $builder->execute());
    }

    public function test_notBetweenWhere()
    {
        $builder = $this->modelsManager->createBuilder()
            ->notBetweenWhere('address_id', 51, 1000000)
            ->addFrom(get_class(new Address()));
        $this->assertCount(50, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->notBetweenWhere('address_id', 51, 1000000)
            ->notBetweenWhere('address_id', 71, 7000000)
            ->addFrom(get_class(new Address()));
        $this->assertCount(50, $builder->execute());
    }

    public function test_inWhere()
    {
        $builder = $this->modelsManager->createBuilder()->inWhere('address_id', [])->addFrom(get_class(new Address()));
        $this->assertCount(0, $builder->execute());

        $builder = $this->modelsManager->createBuilder()->inWhere('address_id', [1])->addFrom(get_class(new Address()));
        $this->assertCount(1, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->inWhere('address_id', [1, 2, 3, 4, 5])
            ->addFrom(get_class(new Address()));
        $this->assertCount(5, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->inWhere('address_id', [-3, -2, -1, 0, 1, 2])
            ->addFrom(get_class(new Address()));
        $this->assertCount(2, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->columns('*')
            ->addFrom(get_class(new Address()))
            ->inWhere('city_id', $this->modelsManager->createBuilder()
                ->columns('city_id')
                ->addFrom(get_class(new Address()))
                ->inWhere('city_id', [1, 2, 3, 4]));
        $this->assertCount(4, $builder->execute());
    }

    public function test_likeWhere()
    {
        $builder = $this->modelsManager->createBuilder()->addFrom(get_class(new Address()))->likeWhere('address', '14%');
        $this->assertCount(33, $builder->execute());

        $builder = $this->modelsManager->createBuilder()->addFrom(get_class(new Address()))->likeWhere(['address','district'],'we%');
        $this->assertCount(15, $builder->execute());
    }

    public function test_orderBy()
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('address_id')
            ->addFrom(get_class(new Address()))
            ->where('address_id <=:max_address_id', ['max_address_id' => 10])
            ->orderBy('address_id');
        $rows = $builder->execute();
        $this->assertCount(10, $builder->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] < $rows[$i + 1]['address_id']);
        }

        $builder = $this->modelsManager->createBuilder()
            ->columns('address_id')
            ->addFrom(get_class(new Address()))
            ->where('address_id <=:max_address_id', ['max_address_id' => 10])
            ->orderBy('address_id ASC');
        $rows = $builder->execute();
        $this->assertCount(10, $builder->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] < $rows[$i + 1]['address_id']);
        }

        $builder = $this->modelsManager->createBuilder()
            ->columns('address_id')
            ->addFrom(get_class(new Address()))
            ->where('address_id <=:max_address_id', ['max_address_id' => 10])
            ->orderBy('address_id DESC');
        $rows = $builder->execute();
        $this->assertCount(10, $builder->execute());

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $this->assertTrue($rows[$i]['address_id'] > $rows[$i + 1]['address_id']);
        }
    }

    public function test_having()
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('COUNT(city_id) as count_city, country_id')
            ->addFrom(get_class(new City()))
            ->groupBy('country_id')
            ->having('COUNT(city_id) >1');
        $rows = $builder->execute();
        $this->assertCount(67, $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row['count_city'] > 1);
        }

        $builder = $this->modelsManager->createBuilder()
            ->columns('COUNT(city_id) as count_city, country_id')
            ->addFrom(get_class(new City()))
            ->groupBy('country_id')
            ->having('COUNT(city_id) >1 AND COUNT(city_id) <7');
        $rows = $builder->execute();
        $this->assertCount(46, $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row['count_city'] > 1);
            $this->assertTrue($row['count_city'] < 7);
        }

        $builder = $this->modelsManager->createBuilder()
            ->columns('COUNT(city_id) as count_city, country_id')
            ->addFrom(get_class(new City()))
            ->groupBy('country_id')
            ->having('COUNT(city_id) >:min_count AND COUNT(city_id) <:max_count', ['min_count' => 1, 'max_count' => 7]);
        $rows = $builder->execute();
        $this->assertCount(46, $rows);
    }

    public function test_limit()
    {
        //limit without offset
        $builder = $this->modelsManager->createBuilder()->columns('city_id')->addFrom(get_class(new City()))->limit(1);
        $this->assertCount(1, $builder->execute());

        //limit with offset
        $builder = $this->modelsManager->createBuilder()
            ->columns('city_id')
            ->addFrom(get_class(new City()))
            ->orderBy('city_id')
            ->limit(10, 20);

        $rows = $builder->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals(21, $rows[0]['city_id']);
        $this->assertEquals(30, $rows[9]['city_id']);
    }

    public function test_page()
    {
        //limit without offset
        $builder = $this->modelsManager->createBuilder()->columns('city_id')->addFrom(get_class(new City()))->limit(1);
        $this->assertCount(1, $builder->execute());

        //limit with offset
        $builder = $this->modelsManager->createBuilder()
            ->columns('city_id')
            ->addFrom(get_class(new City()))
            ->orderBy('city_id')
            ->page(10, 3);

        $rows = $builder->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals(21, $rows[0]['city_id']);
        $this->assertEquals(30, $rows[9]['city_id']);
    }

    public function test_groupBy()
    {
        $builder = $this->modelsManager->createBuilder()
            ->columns('COUNT(city_id) as count_city, country_id')
            ->addFrom(get_class(new City()))
            ->groupBy('country_id');
        $rows = $builder->execute();
        $this->assertCount(109, $rows);

        $builder = $this->modelsManager->createBuilder()
            ->columns('COUNT(payment_id) AS payment_times, customer_id, amount')
            ->addFrom(get_class(new Payment()))
            ->groupBy('customer_id,amount');
        $rows = $builder->execute();
        $this->assertCount(4812, $rows);
    }

    public function test_notInWhere()
    {
        $rowAddress = Address::count();
        $this->assertEquals(603, $rowAddress);

        $builder = $this->modelsManager->createBuilder()
            ->notInWhere('address_id', [])
            ->addFrom(get_class(new Address()));
        $this->assertCount(603, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->notInWhere('address_id', [1])
            ->addFrom(get_class(new Address()));
        $this->assertCount(602, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->notInWhere('address_id', [1, 2, 3])
            ->addFrom(get_class(new Address()));
        $this->assertCount(600, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->notInWhere('address_id', [-3, -2, -1, 0, 1, 2])
            ->addFrom(get_class(new Address()));
        $this->assertCount(601, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->columns('*')
            ->addFrom(get_class(new Address()))
            ->notInWhere('city_id', $this->modelsManager->createBuilder()
                ->columns('city_id')
                ->addFrom(get_class(new Address()))
                ->inWhere('city_id', [1, 2, 3, 4]));
        $this->assertCount(599, $builder->execute());
    }

    public function test_executeEx()
    {
        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->executeEx($totalRows);
        $this->assertCount(600, $rows);
        $this->assertEquals(600, $totalRows);

        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->limit(100)
            ->executeEx($totalRows);
        $this->assertCount(100, $rows);
        $this->assertEquals(600, $totalRows);

        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->limit(1000)
            ->executeEx($totalRows);
        $this->assertCount(600, $rows);
        $this->assertEquals(600, $totalRows);

        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->limit(200, 100)
            ->executeEx($totalRows);
        $this->assertCount(200, $rows);
        $this->assertEquals(600, $totalRows);

        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->limit(300, 1000)
            ->executeEx($totalRows);
        $this->assertCount(0, $rows);
        $this->assertEquals(600, $totalRows);

        $rows = $this->modelsManager->createBuilder()
            ->addFrom(City::class)
            ->limit(10, 10)
            ->groupBy('country_id')
            ->executeEx($totalRows);
        $this->assertCount(10, $rows);
        $this->assertEquals(109, $totalRows);
    }

    public function test_unionAll()
    {
        if ($this->di->getShared('db') instanceof \ManaPHP\Db\Adapter\Sqlite) {
            return;
        }

        $builder = $this->modelsManager->createBuilder()
            ->unionAll([
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5])
            ]);
        $this->assertCount(5, $builder->execute());

        $builder = $this->modelsManager->createBuilder()
            ->unionAll([
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5])
            ])
            ->orderBy('city_id DESC');

        $rows = $builder->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals('5', $rows[0]['city_id']);
        $this->assertEquals('5', $rows[1]['city_id']);

        $builder = $this->modelsManager->createBuilder()
            ->unionAll([
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5])
            ])
            ->orderBy('city_id ASC');

        $rows = $builder->execute();
        $this->assertCount(10, $rows);
        $this->assertEquals('1', $rows[0]['city_id']);
        $this->assertEquals('1', $rows[1]['city_id']);

        $builder = $this->modelsManager->createBuilder()
            ->unionAll([
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5])
            ])
            ->orderBy('city_id ASC')
            ->limit(5);

        $rows = $builder->execute();
        $this->assertCount(5, $rows);

        $builder = $this->modelsManager->createBuilder()
            ->unionDistinct([
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5]),
                $this->modelsManager->createBuilder()
                    ->columns('*')
                    ->from(City::class)
                    ->inWhere('city_id', [1, 2, 3, 4, 5])
            ]);

        $rows = $builder->execute();
        $this->assertCount(5, $rows);
    }
}