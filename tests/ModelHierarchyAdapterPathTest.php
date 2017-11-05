<?php
namespace Tests;

use ManaPHP\Model\Hierarchy\Adapter\Path;
use PHPUnit\Framework\TestCase;

class Category extends \ManaPHP\Db\Model
{
    use Path;
    public static function getHierarchyLengths()
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
        $this->assertTrue(Category::isHierarchyRoot(''));
        $this->assertFalse(Category::isHierarchyRoot('12'));
    }

    public function test_getLevel()
    {
        $this->assertEquals(0, Category::getHierarchyLevel(''));
        $this->assertEquals(1, Category::getHierarchyLevel('11'));
        $this->assertEquals(2, Category::getHierarchyLevel('11222'));
        $this->assertEquals(3, Category::getHierarchyLevel('112223'));
        $this->assertEquals(-1, Category::getHierarchyLevel('1122233'));
        $this->assertEquals(-1, Category::getHierarchyLevel('1'));
    }

    public function test_getParentLength()
    {
        $this->assertEquals(-1, Category::getHierarchyParentLength(''));
        $this->assertEquals(0, Category::getHierarchyParentLength('11'));
        $this->assertEquals(2, Category::getHierarchyParentLength('11222'));
        $this->assertEquals(5, Category::getHierarchyParentLength('112223'));
        $this->assertEquals(-1, Category::getHierarchyParentLength('1122233'));
        $this->assertEquals(-1, Category::getHierarchyParentLength('1'));
    }

    public function test_getParent()
    {
        $this->assertEquals(false, Category::getHierarchyParent(''));
        $this->assertEquals('', Category::getHierarchyParent('11'));
        $this->assertEquals('11', Category::getHierarchyParent('11222'));
        $this->assertEquals('11222', Category::getHierarchyParent('112223'));
        $this->assertEquals(false, Category::getHierarchyParent('1122233'));
        $this->assertEquals(false, Category::getHierarchyParent('1'));
    }

    public function test_getParents()
    {
        $this->assertEquals(false, Category::getHierarchyParents(''));
        $this->assertEquals([''], Category::getHierarchyParents('11'));
        $this->assertEquals(['', '11'], Category::getHierarchyParents('11222'));
        $this->assertEquals(['', '11', '11222'], Category::getHierarchyParents('112223'));
        $this->assertEquals(false, Category::getHierarchyParents('1122233'));
        $this->assertEquals(false, Category::getHierarchyParents('1'));
    }

    public function test_getChildLength()
    {
        $this->assertEquals(2, Category::getHierarchyChildLength(''));
        $this->assertEquals(5, Category::getHierarchyChildLength('11'));
        $this->assertEquals(6, Category::getHierarchyChildLength('11222'));
        $this->assertEquals(-1, Category::getHierarchyChildLength('112223'));
        $this->assertEquals(-1, Category::getHierarchyChildLength('1122233'));
        $this->assertEquals(-1, Category::getHierarchyChildLength('1'));
    }

    public function test_calcNextSibling()
    {
        $this->assertEquals('02', Category::calcHierarchyNextSibling('01'));
        $this->assertEquals('99', Category::calcHierarchyNextSibling('98'));
        $this->assertEquals(false, Category::calcHierarchyNextSibling('zz'));
        $this->assertEquals('11001', Category::calcHierarchyNextSibling('11000'));
        $this->assertEquals('11999', Category::calcHierarchyNextSibling('11998'));
        $this->assertEquals(false, Category::calcHierarchyNextSibling('11zzz'));
        $this->assertEquals('112229', Category::calcHierarchyNextSibling('112228'));
        $this->assertEquals(false, Category::calcHierarchyNextSibling('11222z'));
        $this->assertEquals(false, Category::calcHierarchyNextSibling('1122233'));

        $this->assertEquals(false, Category::calcHierarchyNextSibling('1'));
    }

    public function test_getMaxLength()
    {
        $this->assertEquals(6, Category::getHierarchyMaxLength());
    }
}