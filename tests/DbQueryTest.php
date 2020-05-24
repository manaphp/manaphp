<?php

namespace Tests;

use ManaPHP\Db;
use ManaPHP\Db\Query;
use ManaPHP\DbInterface;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;

class DbQueryTest extends TestCase
{
    public function setUp()
    {
        $di = new Factory();
        $di->alias->set('@data', __DIR__ . '/tmp/data');

        $config = require __DIR__ . '/config.database.php';
        $di->db = $db = new Db($config['mysql']);

        $db->attachEvent('db:beforeQuery', function (DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        });

        echo get_class($db), PHP_EOL;
    }

    public function test_select()
    {
        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->select(['*'])->from('city')->getSql());

        $this->assertEquals('SELECT [city_id], [city_name] FROM [city]',
            (new Query())->select(['city_id', 'city_name'])->from('city')->getSql());

        $this->assertEquals('SELECT [city_id] AS [id], [city_name] FROM [city]',
            (new Query())->select(['id' => 'city_id', 'city_name'])->from('city')->getSql());

        $this->assertEquals('SELECT [city_id], [city_name] FROM [city]',
            (new Query())->select(['city_id', 'city_name'])->from('city')->getSql());

        $this->assertEquals('SELECT [city_id] AS [id], [city_name] FROM [city]',
            (new Query())->select(['id' => 'city_id', 'city_name'])->from('city')->getSql());

        $this->assertEquals('SELECT SUM(city_id) AS [sum], [city_name] FROM [city]',
            (new Query())->select(['sum' => 'SUM(city_id)', 'city_name'])->from('city')->getSql());

        $this->assertEquals('SELECT [c].[city_id] FROM [city] AS [c]',
            (new Query())->select(['c.city_id'])->from('city', 'c')->getSql());

        $this->assertEquals('SELECT [city_id] [id] FROM [city]',
            (new Query())->select(['city_id id'])->from('city')->getSql());
    }

    public function test_from()
    {
        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c]',
            (new Query())->from('city', 'c')->getSql());

        $this->assertEquals('SELECT * FROM [db].[city]',
            (new Query())->from('db.city')->getSql());

        $this->assertEquals('SELECT * FROM [db].[city] AS [c]',
            (new Query())->from('db.city', 'c')->getSql());
    }

    public function test_join()
    {
        $this->assertEquals('SELECT * FROM [city] LEFT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->join('country', 'country.city_id=city.city_id', null, 'LEFT')->getSql());

        $this->assertEquals('SELECT * FROM [city] LEFT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->join('country', 'country.city_id=city.city_id', null, 'LEFT')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c1] LEFT JOIN [country] AS [c2] ON [c2].[city_id]=[c1].[city_id]',
            (new Query())->from('city', 'c1')->join('country', 'c2.city_id=c1.city_id', 'c2', 'LEFT')->getSql());
    }

    public function test_innerJoin()
    {
        $this->assertEquals('SELECT * FROM [city] INNER JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->innerJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] INNER JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->innerJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c1] INNER JOIN [country] AS [c2] ON [c2].[city_id]=[c1].[city_id]',
            (new Query())->from('city', 'c1')->innerJoin('country', 'c2.city_id=c1.city_id', 'c2')->getSql());
    }

    public function test_leftJoin()
    {
        $this->assertEquals('SELECT * FROM [city] LEFT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->leftJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] LEFT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->leftJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c1] LEFT JOIN [country] AS [c2] ON [c2].[city_id]=[c1].[city_id]',
            (new Query())->from('city', 'c1')->leftJoin('country', 'c2.city_id=c1.city_id', 'c2')->getSql());
    }

    public function test_rightJoin()
    {
        $this->assertEquals('SELECT * FROM [city] RIGHT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->rightJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] RIGHT JOIN [country] ON [country].[city_id]=[city].[city_id]',
            (new Query())->from('city')->rightJoin('country', 'country.city_id=city.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c1] RIGHT JOIN [country] AS [c2] ON [c2].[city_id]=[c1].[city_id]',
            (new Query())->from('city', 'c1')->rightJoin('country', 'c2.city_id=c1.city_id', 'c2')->getSql());
    }

    public function test_where()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            (new Query())->from('city')->where(['city_id' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            (new Query())->from('city')->where(['city_id' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE city_id = 1',
            (new Query())->from('city')->where(['city_id = 1'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            (new Query())->from('city')->where(['city_id=' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]!=:city_id',
            (new Query())->from('city')->where(['city_id!=' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]>:city_id',
            (new Query())->from('city')->where(['city_id>' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]>=:city_id',
            (new Query())->from('city')->where(['city_id>=' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]<:city_id',
            (new Query())->from('city')->where(['city_id<' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]<=:city_id',
            (new Query())->from('city')->where(['city_id<=' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [c].[city_id]=:c_city_id',
            (new Query())->from('city')->where(['c.city_id' => 1])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [c].[city_id]>=:c_city_id',
            (new Query())->from('city')->where(['c.city_id>=' => 1])->getSql());

        $result = (new Query())->from('city')->where(['city^=' => 'Ab'])->all();
        $this->assertCount(2, $result);

        $result = (new Query())->from('city')->where(['city$=' => 'a'])->all();
        $this->assertCount(125, $result);

        $result = (new Query())->from('city')->where(['city*=' => 'a'])->all();
        $this->assertCount(450, $result);

        $result = (new Query())->from('city')->where(['city_id' => [1, 2, 3, 4]])->all();
        $this->assertCount(4, $result);

        $result = (new Query())->from('city')->where(['city_id~=' => [1, 4]])->all();
        $this->assertCount(4, $result);

        $result = (new Query())->from('city')->where(['city_id' => []])->all();
        $this->assertCount(0, $result);

        $this->assertEquals('SELECT * FROM [city] WHERE city_id>0',
            (new Query())->from('city')->where(['city_id>0'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IS NULL',
            (new Query())->from('city')->where(['city_id'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IS NULL',
            (new Query())->from('city')->where(['city_id' => null])->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->where(['city_id?' => null])->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->where(['city_id?' => ''])->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->where(['city_id?' => ''])->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->where(['city_id?' => ' '])->getSql());

        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->where(['city_id?' => ' '])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            (new Query())->from('city')->where(['city_id?' => '12'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id]=:city_id',
            (new Query())->from('city')->where(['city_id?' => '12'])->getSql());

    }

    public function test_whereInset()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE FIND_IN_SET(:city_id, [city_id])>0',
            (new Query())->from('city')->whereInset('city_id', 1)->getSql());
    }

    public function test_whereNotInset()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE FIND_IN_SET(:city_id, [city_id])=0',
            (new Query())->from('city')->whereNotInset('city_id', 1)->getSql());
    }

    public function test_whereBetween()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] BETWEEN :city_id_min AND :city_id_max',
            (new Query())->from('city')->whereBetween('city_id', 1, 10)->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [c].[city_id] BETWEEN :c_city_id_min AND :c_city_id_max',
            (new Query())->from('city')->whereBetween('c.city_id', 1, 10)->getSql());

        $this->assertCount(20, (new Query)->from('city')->whereBetween('city_id', 1, 20)->all());
    }

    public function test_whereNotBetween()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT BETWEEN :_min_0 AND :_max_0',
            (new Query())->from('city')->whereNotBetween('city_id', 1, 10)->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [c].[city_id] NOT BETWEEN :_min_0 AND :_max_0',
            (new Query())->from('city')->whereNotBetween('c.city_id', 1, 10)->getSql());

        $this->assertCount(580, (new Query)->from('city')->whereNotBetween('city_id', 1, 20)->all());
    }

    public function test_whereIn()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE FALSE',
            (new Query())->from('city')->whereIn('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (1)',
            (new Query())->from('city')->whereIn('city_id', [1])->getSql());
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (:_in_0_0)',
            (new Query())->from('city')->whereIn('city_id', ['1'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (1, 2)',
            (new Query())->from('city')->whereIn('city_id', [1, 2])->getSql());
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] IN (:_in_0_0, :_in_0_1)',
            (new Query())->from('city')->whereIn('city_id', ['1', '2'])->getSql());
    }

    public function test_whereNotIn()
    {
        $this->assertEquals('SELECT * FROM [city]',
            (new Query())->from('city')->whereNotIn('city_id', [])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (1)',
            (new Query())->from('city')->whereNotIn('city_id', [1])->getSql());
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (:_in_0_0)',
            (new Query())->from('city')->whereNotIn('city_id', ['1'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (1, 2)',
            (new Query())->from('city')->whereNotIn('city_id', [1, 2])->getSql());
        $this->assertEquals('SELECT * FROM [city] WHERE [city_id] NOT IN (:_in_0_0, :_in_0_1)',
            (new Query())->from('city')->whereNotIn('city_id', ['1', '2'])->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE DATE(created_time) NOT IN (2000, 2001)',
            (new Query())->from('city')->whereNotIn('DATE(created_time)', [2000, 2001])->getSql());
        $this->assertEquals('SELECT * FROM [city] WHERE DATE(created_time) NOT IN (:_in_0_0, :_in_0_1)',
            (new Query())->from('city')->whereNotIn('DATE(created_time)', ['2000', '2001'])->getSql());
    }

    public function test_whereContains()
    {
        $this->assertEquals('SELECT * FROM [city] WHERE [city_name] LIKE :city_name',
            (new Query())->from('city')->whereContains('city_name', 'A')->getSql());

        $this->assertEquals('SELECT * FROM [city] AS [c] WHERE [c].[city_name] LIKE :c_city_name',
            (new Query())->from('city', 'c')->whereContains('c.city_name', 'A')->getSql());

        $this->assertEquals('SELECT * FROM [city] WHERE ([city_name] LIKE :city_name OR [country_name] LIKE :country_name)',
            (new Query())->from('city')->whereContains(['city_name', 'country_name'], 'A')->getSql());
    }

    public function test_whereLike()
    {
        $this->assertEquals(0, (new Query())->from('city')->whereLike('city', 'A')->count());
        $this->assertEquals(43, (new Query())->from('city')->whereLike('city', 'A%')->count());
        $this->assertEquals(125, (new Query())->from('city')->whereLike('city', '%A')->count());
        $this->assertEquals(450, (new Query())->from('city')->whereLike('city', '%A%')->count());
        $this->assertEquals(4, (new Query())->from('city')->whereLike('city', 'A___')->count());
        $this->assertEquals(83, (new Query())->from('city')->whereLike('city', '%A___')->count());
    }

    public function test_whereRegex()
    {
        $this->assertEquals(46, (new Query())->from('city')->whereRegex('city', 'A')->count());
        $this->assertEquals(450, (new Query())->from('city')->whereRegex('city', 'A', 'i')->count());
        $this->assertEquals(0, (new Query())->from('city')->whereRegex('city', 'A$')->count());
        $this->assertEquals(125, (new Query())->from('city')->whereRegex('city', 'A$', 'i')->count());
        $this->assertEquals(38, (new Query())->from('city')->whereRegex('city', '^A')->count());
        $this->assertEquals(43, (new Query())->from('city')->whereRegex('city', '^A', 'i')->count());
        $this->assertEquals(38, (new Query())->from('city')->whereRegex('city', 'A....')->count());
        $this->assertEquals(287, (new Query())->from('city')->whereRegex('city', 'A....', 'i')->count());
        $this->assertEquals(34, (new Query())->from('city')->whereRegex('city', '^A....')->count());
        $this->assertEquals(39, (new Query())->from('city')->whereRegex('city', '^A....', 'i')->count());
    }

    public function test_where1v1()
    {
        $query = (new Query())->from('city')->where1v1('city_id,country_id', '10');
        $this->assertEquals('SELECT * FROM [city] WHERE (city_id=:city_id OR country_id=:country_id)', $query->getSql());
        $this->assertEquals(['city_id' => '10', 'country_id' => '10'], $query->getBind());

        $query = (new Query())->from('city')->where1v1('city_id,country_id', '10,20');
        $this->assertEquals('SELECT * FROM [city] WHERE ((city_id=:city_id_a AND country_id=:country_id_b) OR (city_id=:city_id_b AND country_id=:country_id_a))',
            $query->getSql());
        $this->assertEquals(['city_id_a' => '10', 'country_id_b' => '20', 'city_id_b' => '20', 'country_id_a' => '10'], $query->getBind());
    }

    public function test_limit()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10',
            (new Query())->from('city')->limit(10)->getSql());

        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            (new Query())->from('city')->limit(10, 20)->getSql());

        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            (new Query())->from('city')->limit('10', '20')->getSql());
    }

    public function test_page()
    {
        $this->assertEquals('SELECT * FROM [city] LIMIT 10',
            (new Query())->from('city')->page(10)->getSql());

        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            (new Query())->from('city')->page(10, 3)->getSql());

        $this->assertEquals('SELECT * FROM [city] LIMIT 10 OFFSET 20',
            (new Query())->from('city')->page('10', '3')->getSql());
    }

    public function test_orderBy()
    {
        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id]',
            (new Query())->from('city')->orderBy('city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] ASC',
            (new Query())->from('city')->orderBy('city_id ASC')->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] DESC',
            (new Query())->from('city')->orderBy('city_id DESC')->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] ASC',
            (new Query())->from('city')->orderBy(['city_id'])->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] ASC',
            (new Query())->from('city')->orderBy(['city_id' => SORT_ASC])->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] ASC, [city_name] DESC',
            (new Query())->from('city')->orderBy(['city_id' => SORT_ASC, 'city_name' => 'DESC'])->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] DESC',
            (new Query())->from('city')->orderBy(['city_id' => SORT_DESC])->getSql());

        $this->assertEquals('SELECT * FROM [city] ORDER BY [city_id] ASC',
            (new Query())->from('city')->orderBy(['city_id' => 'ASC'])->getSql());
    }

    public function test_having()
    {
        $this->assertEquals('SELECT COUNT(*), [city_id] FROM [country] GROUP BY [city_id] HAVING COUNT(*) >10',
            (new Query())->select(['COUNT(*)', 'city_id'])->from('country')->groupBy('city_id')->having('COUNT(*) >10')->getSql());

        $this->assertEquals('SELECT COUNT(*), [city_id] FROM [country] GROUP BY [city_id] HAVING COUNT(*) >10',
            (new Query())->select(['COUNT(*)', 'city_id'])->from('country')->groupBy('city_id')->having(['COUNT(*) >10'])->getSql());

        $this->assertEquals('SELECT COUNT(*), [city_id] FROM [country] GROUP BY [city_id] HAVING (COUNT(*) >10) AND (city_id >10)',
            (new Query())->select(['COUNT(*)', 'city_id'])->from('country')->groupBy('city_id')->having(['COUNT(*) >10', 'city_id >10'])->getSql());
    }

    public function test_groupBy()
    {
        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id]',
            (new Query())->from('city')->groupBy('city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY DATE(create_time)',
            (new Query())->from('city')->groupBy('DATE(create_time)')->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id], [city_name]',
            (new Query())->from('city')->groupBy('city_id, city_name')->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id]',
            (new Query())->from('city')->groupBy(['city_id'])->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY [city_id], [city_name]',
            (new Query())->from('city')->groupBy(['city_id', 'city_name'])->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY DATE(create_time)',
            (new Query())->from('city')->groupBy(['DATE(create_time)'])->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY [c].[city_id]',
            (new Query())->from('city')->groupBy('c.city_id')->getSql());

        $this->assertEquals('SELECT * FROM [city] GROUP BY [c].[city_id]',
            (new Query())->from('city')->groupBy(['c.city_id'])->getSql());
    }

    public function test_count()
    {
        $this->assertEquals(600, (new Query())->from('city')->count());
        $this->assertEquals(3, (new Query())->from('city')->where(['country_id' => 2])->count());
    }

    public function test_exists()
    {
        $this->assertTrue((new Query)->from('city')->exists());
        $this->assertTrue((new Query)->from('city')->where(['city_id' => 1])->exists());
        $this->assertFalse((new Query)->from('city')->where(['city_id' => 0])->exists());
    }

    public function test_aggregate()
    {
        $this->assertEquals(['city_count' => 600],
            (new Query())->from('city')->aggregate(['city_count' => 'COUNT(*)'])[0]);

        $this->assertEquals(['city_count' => 600],
            (new Query())->from('city')->aggregate(['city_count' => 'count(city_id)'])[0]);
    }
}