<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\QueryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;
use ManaPHP\Di\FactoryDefault;
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

        $this->di->set('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);

            $db->attachEvent('db:beforeQuery', function (DbInterface $source) {
                // var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getEmulatedSQL());
            });

            return $db;
        });
    }

    public function test_implicit_hasOne()
    {
        //magic property
        $city = City::first(1);
        $country = $city->country;
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray());

        //magic property and return false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->country);

        //magic method
        $city = City::first(1);
        $country = $city->getCountry()->fetch();
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray());

        //magic method and return false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->getCountry()->fetch());

        //criteria with all fields
        $city = City::first(1, null, ['with' => 'country']);
        $this->assertEquals(87, $city->country->country_id);

        //criteria with explicit fields
        $city = City::first(1, null, ['with' => ['country' => 'country_id, country']]);
        $this->assertNull($city->country->last_update);
        $this->assertEquals(87, $city->country->country_id);

        //criteria with closure and implicit fetch()
        $city = City::first(1, null, ['with' => ['country' => function (QueryInterface $query) {
            return $query->select(['country_id']);
        }]]);
        $this->assertNull($city->country->last_update);
        $this->assertEquals(87, $city->country->country_id);
        $this->assertCount(1, $city->country->toArray());

        //criteria with closure explicit fetch()
        $city = City::first(1, null, ['with' => ['country' => function (QueryInterface $query) {
            return $query->select(['country_id'])->fetch();
        }]]);
        $this->assertNull($city->country->last_update);
        $this->assertEquals(87, $city->country->country_id);
        $this->assertCount(1, $city->country->toArray());
    }

    public function test_explicit_hasOne()
    {
        //magic property
        $city = City::first(1);
        $country = $city->countryExplicit;
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray());

        //magic property and result is false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->countryExplicit);

        //normal method
        $city = City::first(1);
        $country = $city->getCountryExplicit()->fetch();
        $this->assertSame(87, $city->country_id);
        $this->assertEquals(['country_id' => 87, 'country' => 'Spain', 'last_update' => '2006-02-15 04:44:00'], $country->toArray());

        //normal method and result is false
        $city = new City();
        $city->country_id = -1;
        $this->assertNull($city->getCountry()->fetch());

        //criteria with all fields
        $city = City::first(1, null, ['with' => 'countryExplicit']);
        $this->assertEquals(87, $city->countryExplicit->country_id);

        //criteria with explicit fields
        $city = City::first(1, null, ['with' => ['countryExplicit' => 'country_id, country']]);
        $this->assertNull($city->countryExplicit->last_update);
        $this->assertEquals(87, $city->countryExplicit->country_id);

        //criteria with closure and implicit fetch()
        $city = City::first(1, null, ['with' => ['countryExplicit' => function (QueryInterface $query) {
            return $query->select(['country_id']);
        }]]);
        $this->assertNull($city->countryExplicit->last_update);
        $this->assertEquals(87, $city->countryExplicit->country_id);
        $this->assertCount(1, $city->countryExplicit->toArray());

        //criteria with closure explicit fetch()
        $city = City::first(1, null, ['with' => ['countryExplicit' => function (QueryInterface $query) {
            return $query->select(['country_id'])->fetch();
        }]]);
        $this->assertNull($city->countryExplicit->last_update);
        $this->assertEquals(87, $city->countryExplicit->country_id);
        $this->assertCount(1, $city->countryExplicit->toArray());
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

        //criteria with all fields
        $country = Country::first(44, null, ['with' => 'cities']);
        $this->assertCount(60, $country->cities);
        $this->assertCount(4, $country->cities[8]->toArray());

        //criteria with explicit fields
        $country = Country::first(44, null, ['with' => ['cities' => 'city_id, city']]);
        $this->assertCount(60, $country->cities);
        $this->assertCount(2, $country->cities[8]->toArray());
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
        $country = Country::first(44, null, ['with' => 'citiesExplicit']);
        $this->assertCount(60, $country->citiesExplicit);
        $this->assertCount(4, $country->citiesExplicit[8]->toArray());

        //criteria with explicit fields
        $country = Country::first(44, null, ['with' => ['citiesExplicit' => 'city_id, city']]);
        $this->assertCount(60, $country->citiesExplicit);
        $this->assertCount(2, $country->citiesExplicit[8]->toArray());

        //criteria with closure and implicit fetch()
        $country = Country::first(44, null, ['with' => ['citiesExplicit' => function (QueryInterface $query) {
            return $query->select(['city_id', 'city']);
        }]]);
        $this->assertCount(2, $country->citiesExplicit[8]->toArray());

        //criteria with closure explicit fetch()
        $country = Country::first(44, null, ['with' => ['citiesExplicit' => function (QueryInterface $query) {
            return $query->select(['city_id', 'city'])->fetch();
        }]]);
        $this->assertCount(2, $country->citiesExplicit[8]->toArray());
    }

    public function test_hasManyToMany()
    {
        $rental = Rental::first(10, null, ['with' => ['inventory']]);
        $this->assertSame(1824, $rental->inventory->inventory_id);

        $rental = Rental::first(10);
        $this->assertSame(1824, $rental->inventory->inventory_id);

        $rental = Rental::first(10, null, ['with' => ['inventories']]);
        $this->assertCount(21, $rental->inventories);

        $rental = Rental::first(10);
        $this->assertCount(21, $rental->inventories);

        $rental = Rental::first(10, null, ['with' => ['inventoriesOfCustomer']]);
        $this->assertCount(21, $rental->inventoriesOfCustomer);

        $rental = Rental::first(10);
        $this->assertCount(21, $rental->inventoriesOfCustomer);

        $rental = Rental::first(10, null, ['with' => ['customer']]);
        $this->assertSame(399, $rental->customer->customer_id);

        $rental = Rental::first(10);
        $this->assertSame(399, $rental->customer->customer_id);

        $rental = Rental::first(10, null, ['with' => ['customers']]);
        $this->assertCount(5, $rental->customers);

        $rental = Rental::first(10);
        $this->assertCount(5, $rental->customers);

        $rental = Rental::first(10, null, ['with' => ['customersOfInventory']]);
        $this->assertCount(5, $rental->customersOfInventory);

        $rental = Rental::first(10);
        $this->assertCount(5, $rental->customersOfInventory);
    }
}