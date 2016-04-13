<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/23
 * Time: 21:36
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class DbTest extends TestCase
{
    /**
     * @var \ManaPHP\DbInterface
     */
    protected $db;

    public function setUp()
    {
        $config = require 'config.database.php';
        $this->db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
        $this->db->attachEvent('db:beforeQuery', function ($event, \ManaPHP\DbInterface $source, $data) {
            //var_dump(['sql'=>$source->getSQLStatement(),'bind'=>$source->getSQLBindParams(),'bindTypes'=>$source->getSQLBindTypes()]);
            //      var_dump($source->getSQLStatement(), $source->getEmulatePrepareSQLStatement(2));

        });
        $this->db->query('SET GLOBAL innodb_flush_log_at_trx_commit=2');
    }

    public function test_query()
    {
        //general usage
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city LIMIT 3');
        $this->assertInstanceOf('\PDOStatement', $statement);
        for ($i = 0; $i < 3; $i++) {
            $row = $statement->fetch();
            $this->assertCount(4, $row);
        }

        $row = $statement->fetch();
        $this->assertFalse($row);

        //check fetched rows is correct
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city LIMIT 5');
        $this->assertTrue(is_object($statement));
        $rowCount = 0;
        while ($statement->fetch()) {
            $rowCount++;
        }
        $this->assertEquals(5, $rowCount);

        //general usage in FETCH_NUM mode
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city LIMIT 5', null,
            PDO::FETCH_NUM);
        $row = $statement->fetch();
        $this->assertCount(4, $row);
        $this->assertTrue(isset($row[0]));
        $this->assertFalse(isset($row['city']));

        //general usage in explicit FETCH_ASSOC mode
        $rows = $this->db->query('SELECT city_id, city, country_id, last_update FROM city LIMIT 5', null,
            PDO::FETCH_ASSOC);
        $row = $rows->fetch();
        $this->assertTrue(is_array($row));
        $this->assertCount(4, $row);
        $this->assertFalse(isset($row[0]));
        $this->assertTrue(isset($row['city']));

        //general usage in explicit FETCH_OBJ mode
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city LIMIT 5', null,
            PDO::FETCH_OBJ);
        $row = $statement->fetch();
        $this->assertTrue(is_object($row));
        $this->assertTrue(isset($row->city));

        //query with bind and has related records
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city WHERE city_id=:city_id',
            [':city_id' => 1]);
        $row = $statement->fetch();
        $this->assertCount(4, $row);

        //query with bind and has related records
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city WHERE city_id=:city_id',
            [':city_id' => [1, PDO::PARAM_INT]]);
        $row = $statement->fetch();
        $this->assertCount(4, $row);

        //query with bind and has not related records
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city WHERE city_id=:city_id',
            [':city_id' => -1]);
        $row = $statement->fetch();
        $this->assertFalse($row);

        //query with bind and has not related records
        $statement = $this->db->query('SELECT city_id, city, country_id, last_update FROM city WHERE city_id=:city_id',
            ['city_id' => -1]);
        $row = $statement->fetch();
        $this->assertFalse($row);
    }

    public function test_execute()
    {
        $affectedRows = $this->db->execute('TRUNCATE TABLE _student');
        $this->assertTrue(is_int($affectedRows));
        //affected rows always are 0
        $this->assertEquals(0, $affectedRows);

        $affectedRows = $this->db->execute('INSERT INTO _student(id,age,name) VALUES(?,?,?)', [1, 20, 'mana']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->execute('UPDATE _student set age=?, name=?', [22, 'mana2']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->execute('DELETE FROM _student WHERE id=?', [1]);
        $this->assertEquals(1, $affectedRows);

        $this->db->execute('TRUNCATE TABLE _student');

        $affectedRows = $this->db->execute('INSERT INTO _student(id,age,name) VALUES(:id,:age,:name)',
            ['id' => 11, 'age' => 220, 'name' => 'mana2']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->execute('UPDATE _student set age=:age, name=:name',
            ['age' => 22, 'name' => 'mana2']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->execute('DELETE FROM _student WHERE id=:id', ['id' => 11]);
        $this->assertEquals(1, $affectedRows);
    }

    public function test_insert()
    {

        //recommended method without bind value type
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        //recommended method with bind value type completely
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student',
            ['id' => [1, \PDO::PARAM_INT], 'age' => [21, \PDO::PARAM_INT], 'name' => ['mana1', \PDO::PARAM_STR]]);
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        //recommended method with bind value type partly
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student',
            ['id' => 1, 'age' => [21], 'name' => ['mana1', \PDO::PARAM_STR]]);
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        //value only method
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student', [null, 21, 'mana1']);
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        //compatible method
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        for ($i = 0; $i < 10; $i++) {
            $affectedRows = $this->db->insert('_student', ['age' => $i, 'name' => 'mana' . $i]);
            $this->assertEquals(1, $affectedRows);
        }
    }

    public function test_update()
    {
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->assertEquals(1, $affectedRows);

        //recommended method without bind value type
        $affectedRows = $this->db->update('_student', ['age' => 22, 'name' => 'mana2'], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 22, 'mana2'], array_values($row));

        //recommended method with bind value type completely
        $affectedRows = $this->db->update('_student', ['age' => [23, \PDO::PARAM_INT], 'name' => ['mana3', \PDO::PARAM_STR]], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 23, 'mana3'], array_values($row));

        //recommended method with bind value type partly
        $affectedRows = $this->db->update('_student', ['age' => [24], 'name' => ['mana4', \PDO::PARAM_STR]], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 24, 'mana4'], array_values($row));

        //compatible method
        $affectedRows = $this->db->update('_student', ['age' => 25, 'name' => 'mana5'], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 25, 'mana5'], array_values($row));
    }

    public function test_delete()
    {
        $this->db->execute('TRUNCATE TABLE _student');
        $affectedRows = $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->assertEquals(1, $affectedRows);
        $this->db->delete('_student', 'id=:id', ['id' => 1]);
        $this->assertFalse($this->db->fetchOne('SELECT * FROM _student WHERE id=1'));

        $affectedRows = $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->assertEquals(1, $affectedRows);
        $this->db->delete('_student', 'id=1');
        $this->assertFalse($this->db->fetchOne('SELECT * FROM _student WHERE id=1'));
    }
}