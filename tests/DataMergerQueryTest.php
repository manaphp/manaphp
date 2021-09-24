<?php

namespace Tests;

use ManaPHP\Data\Db\Adapter\Mysql;
use ManaPHP\Data\DbInterface;
use ManaPHP\Data\Merger\Query;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;

class DataMergerQueryTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = new FactoryDefault();
        $this->container->getShared('alias')->set('@data', __DIR__);
        $this->container->setShared(
            'db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);

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

    public function test_limit()
    {
        $models = [City::class, City::class];

        $this->assertEquals([1, 2, 3], array_column((new Query($models))->limit(3)->fetch(true), 'city_id'));
        $this->assertEquals([2, 3, 4], array_column((new Query($models))->limit(3, 1)->fetch(true), 'city_id'));
        $this->assertEquals([1, 2, 3], array_column((new Query($models))->limit(3)->fetch(true), 'city_id'));
        $this->assertEquals([600, 1, 2], array_column((new Query($models))->limit(3, 599)->fetch(true), 'city_id'));
        $this->assertEquals([11, 12, 13], array_column((new Query($models))->limit(3, 610)->fetch(true), 'city_id'));

        $this->assertEquals(
            [306, 306, 307, 307],
            array_column((new Query($models))->orderBy(['city_id' => SORT_ASC])->limit(4, 610)->fetch(true), 'city_id')
        );
        $this->assertEquals(
            [306, 306, 307, 307],
            array_column((new Query($models))->orderBy(['city_id' => SORT_ASC])->limit(4, 610)->fetch(true), 'city_id')
        );
        $this->assertEquals(
            [295, 295, 294, 294],
            array_column((new Query($models))->orderBy(['city_id' => SORT_DESC])->limit(4, 610)->fetch(true), 'city_id')
        );
        $this->assertEquals(
            [295, 295, 294, 294],
            array_column((new Query($models))->orderBy(['city_id' => SORT_DESC])->limit(4, 610)->fetch(true), 'city_id')
        );
        $this->assertEquals(
            [10, 10, 598, 598], array_column(
                (new Query($models))->orderBy(['country_id' => SORT_ASC, 'city_id' => SORT_DESC])->limit(4, 610)->fetch(
                    true
                ), 'city_id'
            )
        );
        $this->assertEquals(
            [376, 376, 355, 355], array_column(
                (new Query($models))->orderBy(['country_id' => SORT_DESC, 'city_id' => SORT_DESC])->limit(4, 610)
                    ->fetch(
                        true
                    ), 'city_id'
            )
        );
        $this->assertEquals(
            [376, 376, 355, 355], array_column(
                (new Query($models))->orderBy(['country_id' => SORT_DESC, 'city_id' => SORT_DESC])->limit(4, 610)
                    ->fetch(
                        true
                    ), 'city_id'
            )
        );
        $this->assertEquals(
            [376, 376, 355, 355], array_column(
                (new Query($models))->orderBy(['country_id' => SORT_DESC])->orderBy(['city_id' => SORT_DESC])->limit(
                    4, 610
                )
                    ->fetch(true), 'city_id'
            )
        );
    }
}