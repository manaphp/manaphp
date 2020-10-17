<?php

namespace Tests;

use ManaPHP\Helper\Sharding;
use PHPUnit\Framework\TestCase;

class HelpShardingTest extends TestCase
{
    public function test_all()
    {
        $this->assertEquals(['' => ['table']], Sharding::all('', 'table'));
        $this->assertEquals(['db' => ['table']], Sharding::all('db', 'table'));

        $this->assertEquals(['db_a' => ['table'], 'db_b' => ['table']], Sharding::all('db_a,db_b', 'table'));
        $this->assertEquals(['db' => ['table_a', 'table_b']], Sharding::all('db', 'table_a,table_b'));

        $this->assertEquals(['db' => ['table_0', 'table_1']], Sharding::all('db', 'table:id%2'));
        $this->assertEquals(['db_0' => ['table'], 'db_1' => ['table']], Sharding::all('db:id%2', 'table'));
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']], Sharding::all('db:id%2', 'table:id%2')
        );
        $this->assertEquals(
            ['db_0' => ['table_a', 'table_b'], 'db_1' => ['table_a', 'table_b']],
            Sharding::all('db:id%2', 'table_a,table_b')
        );

        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']], Sharding::all('db:id%2', 'table:id%2')
        );
    }

    public function test_multiple()
    {
        $this->assertEquals(['' => ['table']], Sharding::multiple('', 'table', []));
        $this->assertEquals(['db' => ['table']], Sharding::multiple('db', 'table', []));

        $this->assertEquals(['db' => ['table']], Sharding::multiple('db', 'table', ['id' => [1]]));
        $this->assertEquals(['db' => ['table']], Sharding::multiple('db', 'table', ['id' => [1, 2]]));

        $this->assertEquals(['db' => ['table_0', 'table_1']], Sharding::multiple('db', 'table:id%2', []));
        $this->assertEquals(['db' => ['table_1']], Sharding::multiple('db', 'table:id%2', ['id' => 1]));
        $this->assertEquals(['db' => ['table_0']], Sharding::multiple('db', 'table:id%2', ['id' => 2]));
        $this->assertEquals(['db' => ['table_1', 'table_0']], Sharding::multiple('db', 'table:id%2', ['id' => [1, 2]]));
        $this->assertEquals(
            ['db' => ['table_1', 'table_0']], Sharding::multiple('db', 'table:id%2', ['id' => [1, 2, 3]])
        );

        $this->assertEquals(
            ['db_a' => ['table_0', 'table_1'], 'db_b' => ['table_0', 'table_1']],
            Sharding::multiple('db_a,db_b', 'table:id%2', [])
        );
        $this->assertEquals(
            ['db_a' => ['table_0'], 'db_b' => ['table_0']], Sharding::multiple('db_a,db_b', 'table:id%2', ['id' => 0])
        );
        $this->assertEquals(
            ['db_a' => ['table_1'], 'db_b' => ['table_1']], Sharding::multiple('db_a,db_b', 'table:id%2', ['id' => 1])
        );
        $this->assertEquals(
            ['db_a' => ['table_0', 'table_1'], 'db_b' => ['table_0', 'table_1']],
            Sharding::multiple('db_a,db_b', 'table:id%2', ['id' => [0, 1]])
        );
        $this->assertEquals(
            ['db_a' => ['table_0', 'table_1'], 'db_b' => ['table_0', 'table_1']],
            Sharding::multiple('db_a,db_b', 'table:id%2', ['id' => [0, 1, 2]])
        );

        $this->assertEquals(['db_0' => ['table'], 'db_1' => ['table']], Sharding::multiple('db:id%2', 'table', []));
        $this->assertEquals(['db_0' => ['table']], Sharding::multiple('db:id%2', 'table', ['id' => 2]));
        $this->assertEquals(['db_1' => ['table']], Sharding::multiple('db:id%2', 'table', ['id' => 1]));
        $this->assertEquals(['db_0' => ['table']], Sharding::multiple('db:id%2', 'table', ['id' => 2]));
        $this->assertEquals(
            ['db_1' => ['table'], 'db_0' => ['table']], Sharding::multiple('db:id%2', 'table', ['id' => [1, 2]])
        );
        $this->assertEquals(
            ['db_1' => ['table'], 'db_0' => ['table']], Sharding::multiple('db:id%2', 'table', ['id' => [1, 2, 3]])
        );
        $this->assertEquals(
            ['db_0' => ['table'], 'db_1' => ['table']], Sharding::multiple('db:id%2', 'table', ['order_id' => 2])
        );

        $this->assertEquals(
            ['db_0' => ['table_a', 'table_b'], 'db_1' => ['table_a', 'table_b']],
            Sharding::multiple('db:id%2', 'table_a,table_b', [])
        );
        $this->assertEquals(
            ['db_0' => ['table_a', 'table_b']], Sharding::multiple('db:id%2', 'table_a,table_b', ['id' => 0])
        );
        $this->assertEquals(
            ['db_1' => ['table_a', 'table_b']], Sharding::multiple('db:id%2', 'table_a,table_b', ['id' => [1]])
        );
        $this->assertEquals(
            ['db_0' => ['table_a', 'table_b'], 'db_1' => ['table_a', 'table_b']],
            Sharding::multiple('db:id%2', 'table_a,table_b', ['id' => [0, 1]])
        );
        $this->assertEquals(
            ['db_0' => ['table_a', 'table_b'], 'db_1' => ['table_a', 'table_b']],
            Sharding::multiple('db:id%2', 'table_a,table_b', ['id' => [0, 1, 2]])
        );

        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:id%2', 'table:id%2', [])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['id' => 1])
        );

        $this->assertEquals(['db_0' => ['table_0']], Sharding::multiple('db:id%2', 'table:id%2', ['id' => [0]]));
        $this->assertEquals(['db_1' => ['table_0']], Sharding::multiple('db:id%2', 'table:id%2', ['id' => [1]]));
        $this->assertEquals(['db_0' => ['table_1']], Sharding::multiple('db:id%2', 'table:id%2', ['id' => [2]]));
        $this->assertEquals(['db_1' => ['table_1']], Sharding::multiple('db:id%2', 'table:id%2', ['id' => [3]]));
        $this->assertEquals(['db_0' => ['table_0']], Sharding::multiple('db:id%2', 'table:id%2', ['id' => [4]]));
        $this->assertEquals(
            ['db_0' => ['table_0'], 'db_1' => ['table_0']],
            Sharding::multiple('db:id%2', 'table:id%2', ['id' => [0, 1]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0']],
            Sharding::multiple('db:id%2', 'table:id%2', ['id' => [0, 1, 2]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:id%2', 'table:id%2', ['id' => [0, 1, 2, 3]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:id%2', 'table:id%2', ['id' => [0, 1, 2, 3, 4]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0'], 'db_1' => ['table_0']],
            Sharding::multiple('db:id%2', 'table:id%2', ['id' => [4, 5]])
        );

        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', [])
        );

        $this->assertEquals(
            ['db_0' => ['table_0'], 'db_1' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['order_id' => 0])
        );
        $this->assertEquals(
            ['db_0' => ['table_1'], 'db_1' => ['table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['order_id' => 1])
        );

        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1']], Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0])
        );
        $this->assertEquals(
            ['db_1' => ['table_0', 'table_1']], Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 1])
        );

        $this->assertEquals(
            ['db_0' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0, 'order_id' => 0])
        );
        $this->assertEquals(
            ['db_0' => ['table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0, 'order_id' => 1])
        );
        $this->assertEquals(
            ['db_1' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 1, 'order_id' => 0])
        );
        $this->assertEquals(
            ['db_1' => ['table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 1, 'order_id' => 1])
        );

        $this->assertEquals(
            ['db_0' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => [0], 'order_id' => 0])
        );
        $this->assertEquals(
            ['db_0' => ['table_0'], 'db_1' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => [0, 1], 'order_id' => 0])
        );

        $this->assertEquals(
            ['db_0' => ['table_0']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0, 'order_id' => [0]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0, 'order_id' => [0, 1]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => 0, 'order_id' => [0, 1, 2]])
        );

        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => [0], 'order_id' => [0]])
        );
        $this->assertEquals(
            ['db_0' => ['table_0', 'table_1'], 'db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => [0, 1], 'order_id' => [0]])
        );
        $this->assertEquals(
            ['db_1' => ['table_0', 'table_1']],
            Sharding::multiple('db:user_id%2', 'table:order_id%2', ['user_id' => [1, 3], 'order_id' => [0]])
        );
    }
}
