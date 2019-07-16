<?php

namespace Tests;

use ManaPHP\Cli\Request;
use PHPUnit\Framework\TestCase;

class CliArgumentsTest extends TestCase
{
    public function test_get()
    {
        $request = new Request(['-r', 'yes']);
        $this->assertEquals('yes', $request->get('recursive:r'));
        $this->assertEquals('', $request->get('all:a', ''));

        $request = new Request(['-r=yes']);
        $this->assertEquals('yes', $request->get('recursive:r'));
        $this->assertEquals('', $request->get('all:a', ''));

        $request = new Request(['/?v=1']);
        $this->assertEquals('1', $request->get('v'));
    }

    public function test_has()
    {
        $request = new Request(['-r']);
        $this->assertTrue($request->has('recursive:r'));
        $this->assertFalse($request->has('all:a'));

        $request = new Request(['-r', '-ab']);
        $this->assertTrue($request->has('recursive:r'));
        $this->assertFalse($request->has('all:a'));

        $request = new Request(['--recursive', '-ab']);
        $this->assertTrue($request->has('recursive:r'));
        $this->assertFalse($request->has('all:a'));

        $request = new Request(['/']);
        $this->assertFalse($request->has('v'));

        $request = new Request(['/?v=']);
        $this->assertTrue($request->has('v'));
    }
}