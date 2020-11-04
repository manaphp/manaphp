<?php

namespace Tests;

use ManaPHP\Db;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;
use Tests\Models\Country;
use Tests\Models\Rental;

class ModelTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $di;

    public function setUp()
    {
        $this->di = new FactoryDefault();
        $this->di->alias->set('@data', __DIR__ . '/tmp/data');
        $this->di->set(
            'db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Db($config['mysql']);

            $db->attachEvent(
                'db:beforeQuery', function (DbInterface $source) {
                // var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getEmulatedSQL());
            }
            );

            return $db;
        }
        );
    }

    public function test_implicit_hasOne()
    {
        //magic property
        $city = City::first(1);
        $country = $city->country;
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(
            ['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray()
        );

        //magic property and return false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->country);

        //magic method
        $city = City::first(1);
        $country = $city->getCountry()->fetch();
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(
            ['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray()
        );

        //magic method and return false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->getCountry()->fetch());

        //criteria with all fields
        $city = City::first(1);
        $this->assertEquals(87, $city->country->country_id);

    }

    public function test_explicit_hasOne()
    {
        //magic property
        $city = City::first(1);
        $country = $city->countryExplicit;
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(
            ['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray()
        );

        //magic property and result is false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->countryExplicit);

        //normal method
        $city = City::first(1);
        $country = $city->getCountryExplicit()->fetch();
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(
            ['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray()
        );

        //normal method and result is false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->getCountry()->fetch());

        //criteria with closure explicit fetch()
        $city = City::first(1);
        $this->assertEquals(87, $city->countryExplicit->country_id);
    }

    public function test_implicit_hasMany()
    {
        //magic property
        $country = Country::first(44);
        $this->assertCount(60, $country->cities);

        //magic property and return is []
        $country = new Country();
        $country->country_id = -1;
        $this->assertCount(0, $country->cities);

        //magic method
        $country = Country::first(44);
        $this->assertCount(60, $country->getCities()->fetch());

        //magic method and return is []
        $country = new Country();
        $country->country_id = -1;
        $this->assertCount(0, $country->cities);

        //criteria with explicit fields
        $country = Country::first(44);
        $this->assertCount(60, $country->cities);
    }

    public function test_explicit_hasMany()
    {
        //property
        $country = Country::first(44);
        $this->assertCount(60, $country->citiesExplicit);

        //property and return is []
        $country = new Country();
        $country->country_id = -1;
        $this->assertCount(0, $country->citiesExplicit);

        //method
        $country = Country::first(44);
        $this->assertCount(60, $country->getCitiesExplicit()->fetch());

        //method and return is []
        $country = new Country();
        $country->country_id = -1;
        $this->assertCount(0, $country->citiesExplicit);

        //criteria with all fields
        $country = Country::first(44);
        $this->assertCount(60, $country->citiesExplicit);

        //criteria with explicit fields
        $country = Country::first(44);
        $this->assertCount(60, $country->citiesExplicit);
    }

    public function test_hasManyToMany()
    {
        $rental = Rental::first(10);
        $this->assertSame(1824, $rental->inventory->inventory_id);

        $rental = Rental::first(10);
        $this->assertCount(21, $rental->inventories);

        $rental = Rental::first(10);
        $this->assertCount(21, $rental->inventories);


        $rental = Rental::first(10);
        $this->assertSame(399, $rental->customer->customer_id);

        $rental = Rental::first(10);
        $this->assertCount(5, $rental->customers);

    }
}
