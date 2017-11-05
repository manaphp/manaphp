<?php
namespace Tests;

use ManaPHP\Hierarchy\Adapter\Path;
use PHPUnit\Framework\TestCase;

class Category extends Path
{
    public static function getLengths()
    {
        return [2, 3, 1];
    }
}

class HierarchyTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_isRoot()
    {
        $this->assertTrue(Category::isRoot(''));
        $this->assertFalse(Category::isRoot('12'));
    }

    public function test_getLevel()
    {
        $this->assertEquals(0, Category::getLevel(''));
        $this->assertEquals(1, Category::getLevel('11'));
        $this->assertEquals(2, Category::getLevel('11222'));
        $this->assertEquals(3, Category::getLevel('112223'));
        $this->assertEquals(-1, Category::getLevel('1122233'));
        $this->assertEquals(-1, Category::getLevel('1'));
    }

    public function test_getParentLength()
    {
        $this->assertEquals(-1, Category::getParentLength(''));
        $this->assertEquals(0, Category::getParentLength('11'));
        $this->assertEquals(2, Category::getParentLength('11222'));
        $this->assertEquals(5, Category::getParentLength('112223'));
        $this->assertEquals(-1, Category::getParentLength('1122233'));
        $this->assertEquals(-1, Category::getParentLength('1'));
    }

    public function test_getParent()
    {
        $this->assertEquals(false, Category::getParent(''));
        $this->assertEquals('', Category::getParent('11'));
        $this->assertEquals('11', Category::getParent('11222'));
        $this->assertEquals('11222', Category::getParent('112223'));
        $this->assertEquals(false, Category::getParent('1122233'));
        $this->assertEquals(false, Category::getParent('1'));
    }

    public function test_getParents()
    {
        $this->assertEquals(false, Category::getParents(''));
        $this->assertEquals([''], Category::getParents('11'));
        $this->assertEquals(['', '11'], Category::getParents('11222'));
        $this->assertEquals(['', '11', '11222'], Category::getParents('112223'));
        $this->assertEquals(false, Category::getParents('1122233'));
        $this->assertEquals(false, Category::getParents('1'));
    }

    public function test_getChildLength()
    {
        $this->assertEquals(2, Category::getChildLength(''));
        $this->assertEquals(5, Category::getChildLength('11'));
        $this->assertEquals(6, Category::getChildLength('11222'));
        $this->assertEquals(-1, Category::getChildLength('112223'));
        $this->assertEquals(-1, Category::getChildLength('1122233'));
        $this->assertEquals(-1, Category::getChildLength('1'));
    }

    public function test_calcNextSibling()
    {
        $this->assertEquals('02', Category::calcNextSibling('01'));
        $this->assertEquals('99', Category::calcNextSibling('98'));
        $this->assertEquals(false, Category::calcNextSibling('zz'));
        $this->assertEquals('11001', Category::calcNextSibling('11000'));
        $this->assertEquals('11999', Category::calcNextSibling('11998'));
        $this->assertEquals(false, Category::calcNextSibling('11zzz'));
        $this->assertEquals('112229', Category::calcNextSibling('112228'));
        $this->assertEquals(false, Category::calcNextSibling('11222z'));
        $this->assertEquals(false, Category::calcNextSibling('1122233'));

        $this->assertEquals(false, Category::calcNextSibling('1'));
    }

    public function test_getMaxLength()
    {
        $this->assertEquals(6, Category::getMaxLength());
    }
}