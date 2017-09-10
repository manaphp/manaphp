<?php

namespace Tests;

use ManaPHP\Cli\Arguments;
use PHPUnit\Framework\TestCase;

class CliArgumentsTest extends TestCase
{
    public function test_get()
    {
        $arguments = new Arguments(['-r', 'yes']);
        $this->assertEquals('yes', $arguments->get('recursive:r'));
        $this->assertEquals(null, $arguments->get('all:a'));
    }

    public function test_has()
    {
        $arguments = new Arguments(['-r']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));

        $arguments = new Arguments(['-r', '-ab']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));

        $arguments = new Arguments(['--recursive', '-ab']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));
    }
}