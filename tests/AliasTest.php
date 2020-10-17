<?php

namespace Tests;

use ManaPHP\Alias;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    public function test_setAlias()
    {
        $alias = new Alias();

        $alias->set('@app', '\data\www\app');
        $this->assertEquals('/data/www/app', $alias->get('@app'));

        $alias->set('@data', '/app/data');
        $alias->set('@log', '@data/log');
        $this->assertEquals('/app/data/log', $alias->get('@log'));

        $alias->set('@log', '@data');
        $this->assertEquals('/app/data', $alias->get('@log'));

        try {
            $alias->set('a', 'fdf');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Exception\MisuseException', $e);
        }
    }

    public function test_get()
    {
        $alias = new Alias();

        $alias->set('@app', '\app');
        $this->assertSame('/app', $alias->get('@app'));

        try {
            $alias->get('app');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Exception\MisuseException', $e);
        }
    }

    public function test_resolve()
    {
        $alias = new Alias();

        $this->assertEquals('/www/app', $alias->resolve('\www\app'));

        $alias->set('@app', '/www/app');
        $this->assertEquals('/www/app/data', $alias->resolve('@app/data'));

        $this->assertEquals('/www/app', $alias->resolve('@app'));
    }
}