<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 16:14
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class DbConditionParserTest extends TestCase
{
    /**
     * @var \ManaPHP\DbInterface
     */
    protected $db;

    public function setUp()
    {
        $config = require __DIR__ . '/config.database.php';
        $this->db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
    }

    public function test_parse()
    {
        $conditionParser = new \ManaPHP\Db\ConditionParser();

        $conditions = $conditionParser->parse('id=1', $binds);
        $this->assertEquals('id=1', $conditions);
        $this->assertEquals([], $binds);

        $conditions = $conditionParser->parse(['id=1', 'city_id' => 2], $binds);
        $this->assertEquals('id=1 AND `city_id`=:city_id', $conditions);
        $this->assertEquals(['city_id' => 2], $binds);

        $conditions = $conditionParser->parse(['id' => 2], $binds);
        $this->assertEquals('`id`=:id', $conditions);
        $this->assertEquals(['id' => 2], $binds);

        $conditions = $conditionParser->parse(['id' => [2]], $binds);
        $this->assertEquals('`id`=:id', $conditions);
        $this->assertEquals(['id' => 2], $binds);

        $conditions = $conditionParser->parse(['id' => [2, 'city_id']], $binds);
        $this->assertEquals('`id`=:city_id', $conditions);
        $this->assertEquals(['city_id' => 2], $binds);

        $conditions = $conditionParser->parse(['age' => 21, 'name' => 'mana'], $binds);
        $this->assertEquals('`age`=:age AND `name`=:name', $conditions);
        $this->assertEquals(['age' => 21, 'name' => 'mana'], $binds);
    }
}