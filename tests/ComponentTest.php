<?php

namespace Tests;

use ManaPHP\Component;
use PHPUnit\Framework\TestCase;

class TClass1 extends Component
{
    public $publicP;
    protected $protectedP;
    private $privateP;

    protected static $staticP;

    public function getPrivatePs()
    {
        return $this->privateP;
    }
}

class ComponentTest extends TestCase
{
    public function test_xx()
    {

    }
//    public function test_hasProperty()
//    {
//        $o = new TClass1();
//        $o->dynamic = 1;
//
//        $this->assertTrue($o->hasProperty('publicP'));
//        $this->assertTrue($o->hasProperty('protectedP'));
//        $this->assertFalse($o->hasProperty('privateP'));
//        $this->assertFalse($o->hasProperty('staticP'));
//        $this->assertTrue($o->hasProperty('dynamic'));
//    }
//
//    public function test_setProperty()
//    {
//        $o = new TClass1();
//
//        $o->setProperty('publicP', 'pub');
//        $o->setProperty('protectedP', 'pro');
//        // $o->setProperty('privateP', 'pri');
//        //$o->setProperty('staticP', 'sta');
//
//        $this->assertEquals('pub', $o->getProperty('publicP'));
//        $this->assertEquals('pro', $o->getProperty('protectedP'));
//        //  $this->assertEquals('pri', $o->getProperty('privateP'));
//        //  $this->assertEquals('sta', $o->getProperty('staticP'));
//    }
//
//    public function test_getProperty()
//    {
//        $o = new TClass1();
//
//        $o->setProperty('publicP', 'pub');
//        $o->setProperty('protectedP', 'pro');
//        // $o->setProperty('privateP', 'pri');
//        //$o->setProperty('staticP', 'sta');
//
//        $this->assertEquals('pub', $o->getProperty('publicP'));
//        $this->assertEquals('pro', $o->getProperty('protectedP'));
//        //  $this->assertEquals('pri', $o->getProperty('privateP'));
//        //  $this->assertEquals('sta', $o->getProperty('staticP'));
//    }
//
//    public function test_getProperties()
//    {
//        $o = new TClass1();
//        $properties = $o->getProperties();
//
//        $this->assertContains('publicP', $properties);
//        $this->assertContains('protectedP', $properties);
//    }
}