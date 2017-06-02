<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class ZookeeperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        new ManaPHP\Di\FactoryDefault();
    }

    public function test_create()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->delete('/manaphp');
        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->create('/manaphp', 'data'));
        $this->assertEquals('data', $zookeeper->getData('/manaphp'));

        $zookeeper->delete('/manaphp');
        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->create('/manaphp/a/b/c'));
        $this->assertEquals('', $zookeeper->getData('/manaphp/a/b/c'));

        $zookeeper->setData('/manaphp/a/b/c', 'data');
        $this->assertEquals('data', $zookeeper->getData('/manaphp/a/b/c'));
    }

    public function test_createNx()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->delete('/manaphp');
        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->createNx('/manaphp', 'data'));
        $this->assertEquals('data', $zookeeper->getData('/manaphp'));

        $zookeeper->delete('/manaphp');
        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->createNx('/manaphp/a/b/c'));
        $this->assertEquals('', $zookeeper->getData('/manaphp/a/b/c'));

        $this->assertTrue(is_array($zookeeper->exists('/manaphp')));
        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->createNx('/manaphp'));
    }

    public function test_delete()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->delete('/manaphp'));
        $this->assertFalse($zookeeper->exists('/manaphp'));

        $zookeeper->create('/manaphp');
        $this->assertTrue(is_array($zookeeper->exists('/manaphp')));

        $this->assertInstanceOf('ManaPHP\Zookeeper', $zookeeper->delete('/manaphp/a/b/c'));
    }

    public function test_setData()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->createNx('/manaphp');

        $zookeeper->setData('/manaphp', 'data');
        $this->assertEquals('data', $zookeeper->getData('/manaphp'));

        $zookeeper->setData('/manaphp', 'data2');
        $this->assertEquals('data2', $zookeeper->getData('/manaphp'));
    }

    public function test_getData()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->delete('/manaphp');
        $this->assertFalse($zookeeper->getData('/manaphp'));

        $zookeeper->create('/manaphp', 'data');
        $this->assertEquals('data', $zookeeper->getData('/manaphp'));
    }

    public function test_getChildren()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->delete('/manaphp');
        $this->assertFalse($zookeeper->getChildren('/manaphp'));

        $zookeeper->create('/manaphp');
        $this->assertEquals([], $zookeeper->getChildren('/manaphp'));

        $zookeeper->create('/manaphp/abc');
        $this->assertEquals(['abc'], $zookeeper->getChildren('/manaphp'));
    }

    public function test_exists()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $zookeeper->delete('/manaphp');
        $this->assertFalse($zookeeper->exists('/manaphp'));

        $zookeeper->create('/manaphp');
        $this->assertTrue(is_array($zookeeper->exists('/manaphp')));
    }

    public function test_watchData()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $count = 0;
        $zookeeper->watchData('/manaphp', function ($e) use (&$count) {
            $count++;
        });

        $this->assertEquals(1, $count);
        sleep(1);
        $zookeeper->setData('/manaphp', '');
        $this->assertEquals(2, $count);
    }

    public function test_watchChildren()
    {
        $zookeeper = new ManaPHP\ZooKeeper('localhost:2181');

        $count = 0;
        
        $zookeeper->watchChildren('/manaphp', function ($e) use (&$count) {
            $count++;
        });

        $this->assertEquals(1, $count);
        $zookeeper->create('/manaphp/a', '');
        sleep(1);
        $this->assertEquals(2, $count);
    }
}