<?php
namespace Tests;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use Tests\Models\City;

class ModelCriteriaTest extends \PHPUnit_Framework_TestCase
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

    public function test_where()
    {
        $this->assertSame(600, City::criteria()->where(null)->count());

        $this->assertSame(3, City::criteria()->where('country_id', 2)->count());
        $this->assertSame(3, City::criteria()->where('country_id=', 2)->count());
        $this->assertSame(596, City::criteria()->where('country_id>', 2)->count());
        $this->assertSame(599, City::criteria()->where('country_id>=', 2)->count());
        $this->assertSame(1, City::criteria()->where('country_id<', 2)->count());
        $this->assertSame(4, City::criteria()->where('country_id<=', 2)->count());
        $this->assertSame(597, City::criteria()->where('country_id!=', 2)->count());

        $this->assertSame(121, City::criteria()->where('city*=', 'b')->count());
        $this->assertSame(121, City::criteria()->where('city*=', 'B')->count());
        $this->assertSame(43, City::criteria()->where('city^=', 'a')->count());
        $this->assertSame(43, City::criteria()->where('city^=', 'A')->count());
        $this->assertSame(75, City::criteria()->where('city$=', 'n')->count());
        $this->assertSame(75, City::criteria()->where('city$=', 'N')->count());

        $this->assertSame(0, City::criteria()->where('country_id', [])->count());
        $this->assertSame(1, City::criteria()->where('country_id', [1])->count());
        $this->assertSame(3, City::criteria()->where('country_id', [2])->count());
        $this->assertSame(4, City::criteria()->where('country_id', [1, 2])->count());

        $this->assertSame(0, City::criteria()->where('country_id=', [])->count());
        $this->assertSame(1, City::criteria()->where('country_id=', [1])->count());
        $this->assertSame(3, City::criteria()->where('country_id=', [2])->count());
        $this->assertSame(4, City::criteria()->where('country_id=', [1, 2])->count());

        $this->assertSame(600, City::criteria()->where('country_id!=', [])->count());
        $this->assertSame(599, City::criteria()->where('country_id!=', [1])->count());
        $this->assertSame(597, City::criteria()->where('country_id!=', [2])->count());
        $this->assertSame(596, City::criteria()->where('country_id!=', [1, 2])->count());
    }
}