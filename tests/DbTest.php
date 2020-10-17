<?php

namespace Tests;

use ManaPHP\Db;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    /**
     * @var \ManaPHP\Db
     */
    protected $db;

    protected $connection;

    public function setUp()
    {
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp/data');

        $config = require __DIR__ . '/config.database.php';

        $this->connection = new Db\Connection\Adapter\Mysql($config['mysql']);
        $this->db = new Db($this->connection);

        $this->db->attachEvent(
            'db:beforeQuery', function (\ManaPHP\DbInterface $source, $data) {
            //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
            var_dump($source->getSQL(), $source->getEmulatedSQL(2));

        }
        );

        echo get_class($this->db), PHP_EOL;
    }

    public function test_execute()
    {
        $this->connection->truncate('_student');

        $affectedRows = $this->db->insertBySql('INSERT INTO _student(id,age,name) VALUES(?,?,?)', [1, 20, 'mana']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->updateBySql('UPDATE _student set age=?, name=?', [22, 'mana2']);
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->deleteBySql('DELETE FROM _student WHERE id=?', [1]);
        $this->assertEquals(1, $affectedRows);

        $this->connection->truncate('_student');

        $affectedRows = $this->db->insertBySql(
            'INSERT INTO _student(id,age,name) VALUES(:id,:age,:name)',
            ['id' => 11, 'age' => 220, 'name' => 'mana2']
        );
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->updateBySql(
            'UPDATE _student set age=:age, name=:name',
            ['age' => 22, 'name' => 'mana2']
        );
        $this->assertEquals(1, $affectedRows);

        $affectedRows = $this->db->deleteBySql('DELETE FROM _student WHERE id=:id', ['id' => 11]);
        $this->assertEquals(1, $affectedRows);
    }

    public function test_insert()
    {

        //recommended method without bind value type
        $this->connection->truncate('_student');
        $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        //compatible method
        $this->connection->truncate('_student');
        $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 21, 'mana1'], array_values($row));

        for ($i = 0; $i < 10; $i++) {
            $this->db->insert('_student', ['age' => $i, 'name' => 'mana' . $i]);
        }
    }

    public function test_update()
    {
        $this->connection->truncate('_student');
        $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);

        //recommended method without bind value type
        $affectedRows = $this->db->update('_student', ['age' => 22, 'name' => 'mana2'], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 22, 'mana2'], array_values($row));

        //compatible method
        $affectedRows = $this->db->update('_student', ['age' => 25, 'name' => 'mana5'], 'id=1');
        $this->assertEquals(1, $affectedRows);
        $row = $this->db->fetchOne('SELECT id,age,name FROM _student WHERE id=1');
        $this->assertEquals([1, 25, 'mana5'], array_values($row));
    }

    public function test_delete()
    {
        $this->connection->truncate('_student');
        $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->db->delete('_student', 'id=:id', ['id' => 1]);
        $this->assertFalse($this->db->fetchOne('SELECT * FROM _student WHERE id=1'));

        $this->db->insert('_student', ['id' => 1, 'age' => 21, 'name' => 'mana1']);
        $this->db->delete('_student', 'id=1');
        $this->assertFalse($this->db->fetchOne('SELECT * FROM _student WHERE id=1'));
    }
}