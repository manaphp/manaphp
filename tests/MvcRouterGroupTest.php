<?php
namespace Tests;

use ManaPHP\Mvc\Router\Group;
use PHPUnit\Framework\TestCase;

class MvcRouterGroupTest extends TestCase
{
    public function test_construct()
    {
        $group = new Group();

        $this->assertEquals([], $group->match('/'));
        $this->assertEquals(['controller' => 'a'], $group->match('/a'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b'], $group->match('/a/b'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => 'c'], $group->match('/a/b/c'));
        $this->assertEquals(['controller' => 'a', 'action' => 'b', 'params' => 'c/d/e'], $group->match('/a/b/c/d/e'));
    }
}
