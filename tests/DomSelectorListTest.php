<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Dom\Selector;

class DomSelectorListTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_FILE = __DIR__ . '/Dom/sample.html';

    public function setUp()
    {
        parent::setUp();

        new FactoryDefault();
    }

    public function test_add()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(14, $selector->find('li'));
        $this->assertCount(3, $selector->find('ul'));
        $this->assertCount(17, $selector->find('li')->add($selector->find('ul')));
        $this->assertCount(17, $selector->find('li')->add('ul'));
        $this->assertCount(14, $selector->find('li')->add('not_exists'));

        $this->assertCount(2, $selector->find('li.current'));
        $this->assertCount(14, $selector->find('li'));
        //  $this->assertCount(14, $selector->find('li.current')->add($selector->find('li')));
//        $this->assertCount(14, $selector->find('li.current')->add('li'));
    }

    public function test_children()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(0, $selector->find('not_exists')->children());
        $this->assertCount(4, $selector->find('#topnav')->children());
        $this->assertCount(1, $selector->find('#topnav')->children('.current'));
    }

    public function test_closest()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(0, $selector->find('.subtitle')->closest('not_exits'));
        $this->assertEquals(
            '/html/body/div[1]/h1/span', (string)$selector->find('.subtitle')->closest('span')->first()
        );
        $this->assertEquals('/html/body/div[1]', (string)$selector->find('.subtitle')->closest('#header')->first());
    }

    public function test_eq()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertEquals('/html/body/ul/li[1]', (string)$selector->find('#topnav li')->eq(0)->first());
        $this->assertEquals('/html/body/ul/li[2]', (string)$selector->find('#topnav li')->eq(1)->first());
        $this->assertEquals('/html/body/ul/li[2]', (string)$selector->find('#topnav li')->eq(-3)->first());
        $this->assertCount(0, $selector->find('#topnav li')->eq(-5));
    }

    public function test_find()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(59, $selector->find());
        $this->assertCount(18, $selector->find('ul')->find());
        $this->assertCount(7, $selector->find('a'));
        $this->assertCount(4, $selector->find('a[href="#"]'));
        $this->assertCount(4, $selector->find('div[class]'));
    }

    public function test_first()
    {

    }

    public function test_has()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(14, $selector->find('li'));
        $this->assertCount(3, $selector->find('li')->has('a[href="#"]'));
        $this->assertCount(0, $selector->find('li')->has('not_exists'));
    }

    public function test_is()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertTrue($selector->find('li')->is('.current'));
        $this->assertFalse($selector->find('li')->is('not_exits'));
    }

    public function test_next()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertEquals('/html/body/ul/li[2]', (string)$selector->find('li.current')->next()->first());
        $this->assertEquals(
            '/html/body/ul/li[3]/a', (string)$selector->find('li.current')->next('li a[href="#"]')->first()
        );
        $this->assertCount(0, $selector->find('li.current')->next('not_exists'));
    }

    public function test_nextAll()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(2, $selector->find('li.foo')->nextAll());
        $this->assertCount(1, $selector->find('li.foo')->nextAll('.current'));
    }

    public function test_not()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        // $this->assertCount(1, $selector->find('li')->not('.current'));
    }

    public function test_parent()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(6, $selector->find('a')->parent());
        $this->assertCount(4, $selector->find('a')->parent('li'));
    }

    public function test_parents()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(11, $selector->find('a')->parents());
        $this->assertCount(4, $selector->find('a')->parents('li'));
    }

    public function test_prev()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertEquals(
            '/html/body/div[2]/div[2]/div[2]/ul/li[1]', (string)$selector->find('li.foo.current')->prev()->first()
        );
        $this->assertEquals(
            '/html/body/div[2]/div[2]/div[2]/ul/li[1]', (string)$selector->find('li.foo.current')->prev('.foo')->first()
        );
    }

    public function test_prevAll()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        $this->assertCount(2, $selector->find('#main')->prevAll());
        $this->assertCount(1, $selector->find('#main')->prevAll('#header'));
    }

    public function test_siblings()
    {
        $selector = new Selector(self::SAMPLE_FILE);

        //    $this->assertCount(60, $selector->find('li')->eq(0)->siblings());
        //   $this->assertCount(60, $selector->find('li')->siblings(''));
    }
}