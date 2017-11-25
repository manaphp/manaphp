<?php

namespace Tests;

use ManaPHP\Cli\Arguments;
use PHPUnit\Framework\TestCase;

class CliArgumentsTest extends TestCase
{
    public function test_get()
    {
        $arguments = new Arguments(['-r', 'yes']);
        $this->assertEquals('yes', $arguments->getOption('recursive:r'));
        $this->assertEquals(null, $arguments->getOption('all:a'));

        $arguments = new Arguments(['/?v=1']);
        $this->assertEquals('1', $arguments->getOption('v'));
    }

    public function test_has()
    {
        $arguments = new Arguments(['-r']);
        $this->assertTrue($arguments->hasOption('recursive:r'));
        $this->assertFalse($arguments->hasOption('all:a'));

        $arguments = new Arguments(['-r', '-ab']);
        $this->assertTrue($arguments->hasOption('recursive:r'));
        $this->assertFalse($arguments->hasOption('all:a'));

        $arguments = new Arguments(['--recursive', '-ab']);
        $this->assertTrue($arguments->hasOption('recursive:r'));
        $this->assertFalse($arguments->hasOption('all:a'));

        $arguments = new Arguments(['/']);
        $this->assertFalse($arguments->hasOption('v'));

        $arguments = new Arguments(['/?v=']);
        $this->assertTrue($arguments->hasOption('v'));
    }
}