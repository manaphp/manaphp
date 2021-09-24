<?php

namespace Tests;

use ManaPHP\Data\Db;
use ManaPHP\Data\DbInterface;
use ManaPHP\Di\FactoryDefault;
use Tests\Models\City;

class DataModeQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = new FactoryDefault();
        $this->container->alias->set('@data', __DIR__);
        $this->container->setShared(
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

    public function test_where()
    {
        $this->assertSame(600, City::query()->where(null)->count());

        $this->assertSame(3, City::where(['country_id' => 2])->count());
        $this->assertSame(3, City::where(['country_id=' => 2])->count());
        $this->assertSame(596, City::where(['country_id>' => 2])->count());
        $this->assertSame(599, City::where(['country_id>=' => 2])->count());
        $this->assertSame(1, City::where(['country_id<' => 2])->count());
        $this->assertSame(4, City::where(['country_id<=' => 2])->count());
        $this->assertSame(597, City::where(['country_id!=' => 2])->count());

        $this->assertSame(121, City::where(['city*=' => 'b'])->count());
        $this->assertSame(121, City::where(['city*=' => 'B'])->count());
        $this->assertSame(43, City::where(['city^=' => 'a'])->count());
        $this->assertSame(43, City::where(['city^=' => 'A'])->count());
        $this->assertSame(75, City::where(['city$=' => 'n'])->count());
        $this->assertSame(75, City::where(['city$=' => 'N'])->count());

        $this->assertSame(0, City::where(['country_id' => []])->count());
        $this->assertSame(1, City::where(['country_id' => [1]])->count());
        $this->assertSame(3, City::where(['country_id' => [2]])->count());
        $this->assertSame(4, City::where(['country_id' => [1, 2]])->count());

        $this->assertSame(0, City::where(['country_id=' => []])->count());
        $this->assertSame(1, City::where(['country_id=' => [1]])->count());
        $this->assertSame(3, City::where(['country_id=' => [2]])->count());
        $this->assertSame(4, City::where(['country_id=' => [1, 2]])->count());

        $this->assertSame(600, City::where(['country_id!=' => []])->count());
        $this->assertSame(599, City::where(['country_id!=' => [1]])->count());
        $this->assertSame(597, City::where(['country_id!=' => [2]])->count());
        $this->assertSame(596, City::where(['country_id!=' => [1, 2]])->count());
    }

    public function test_toArray()
    {
        $r = City::query()->where(['city_id<' => 10])->fetch(true);
        $this->assertCount(9, $r);
        $this->assertCount(4, $r[0]);
    }

    public function test_with()
    {
        $r = City::query()->where(['city_id<=' => 2])->with(['country'])->fetch(true);
        $this->assertCount(2, $r);
        $this->assertCount(5, $r[0]);
        $this->assertArrayHasKey('country', $r[0]);
        $this->assertEquals(87, $r[0]['country']['country_id']);

        $r = City::query()->where(['city_id<=' => 2])->with(['country' => 'country_id,country'])->fetch(true);
        $this->assertCount(2, $r[0]['country']);

        $r = City::query()->where(['city_id<=' => 2])->with(['country' => ['country_id', 'country']])->fetch(true);
        $this->assertCount(2, $r[0]['country']);

        $r = City::query()->where(['city_id<=' => 2])->with(['country' => ['country_id<' => 0]])->fetch(true);
        $this->assertNull($r[0]['country']);

        $r = City::query()->where(['city_id<=' => 2])->with(['country' => ['country_id, country', 'country_id<' => 0]])
            ->fetch(true);
        $this->assertNull($r[0]['country']);
    }

}