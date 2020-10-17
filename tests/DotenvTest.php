<?php

namespace Tests;

use ManaPHP\Dotenv;

class DotenvTest extends \PHPUnit_Framework_TestCase
{
    public function test_parse()
    {
        $dotenv = new Dotenv();

        $this->assertEquals([], $dotenv->parse([]));
        $this->assertEquals([], $dotenv->parse(['#comment']));
        $this->assertEquals(['a' => 'b'], $dotenv->parse(['a=b # comment']));
        $this->assertEquals([], $dotenv->parse(['#CBAR=baz']));
        $this->assertEquals(['A' => true], $dotenv->parse(['A=true']));
        $this->assertEquals(['A' => false], $dotenv->parse(['A=false']));
        $this->assertEquals(['A' => null], $dotenv->parse(['A=null']));
        $this->assertEquals(['A' => 'a'], $dotenv->parse(['A=a']));
        $this->assertEquals(['A' => 'a'], $dotenv->parse(['A="a"']));
        $this->assertEquals(['A' => 'a'], $dotenv->parse(["A='a'"]));
        $this->assertEquals(['A' => ''], $dotenv->parse(['A=""']));
        $this->assertEquals(['VAR3' => '   '], $dotenv->parse(['VAR3="   "']));
        $this->assertEquals(['FOO' => 'bar'], $dotenv->parse(['export FOO="bar"']));
        $this->assertEquals(['A' => "a'"], $dotenv->parse(["A='a\\''"]));
        $this->assertEquals(['A' => 'a"'], $dotenv->parse(['A="a\""']));
        $this->assertEquals(['A' => PHP_EOL], $dotenv->parse(['A=\n']));
        $this->assertEquals(['A' => 'aa', 'B' => 'aa'], $dotenv->parse(['A=aa', 'B=$A']));
        $this->assertEquals(['A' => 'aa', 'B' => 'aa'], $dotenv->parse(['A=aa', 'B=${A}']));
        $this->assertEquals(
            ['N.VAR6' => 'Special Value', 'VAR7' => 'Special Value'],
            $dotenv->parse(['N.VAR6="Special Value"', 'VAR7="${N.VAR6}"'])
        );
    }
}