<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class MvcRouterGroupTest extends TestCase
{
    public function test_construct()
    {
        $group = new ManaPHP\Mvc\Router\Group();

        $this->assertEquals([], $group->match('/'));
        $this->assertEquals(['controller' => 'a'], $group->match('/a'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b'], $group->match('/a/b'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => 'c'], $group->match('/a/b/c'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => 'c/d/e'], $group->match('/a/b/c/d/e'));
    }
}
