<?php

namespace Tests;

use ManaPHP\Cli\Options;
use PHPUnit\Framework\TestCase;

class CliOptionsTest extends TestCase
{
    public function test_get()
    {
        $options = new Options(['-r', 'yes']);
        $this->assertEquals('yes', $options->get('recursive:r'));
        $this->assertEquals('', $options->get('all:a', ''));

        $options = new Options(['-r=yes']);
        $this->assertEquals('yes', $options->get('recursive:r'));
        $this->assertEquals('', $options->get('all:a', ''));

        $options = new Options(['/?v=1']);
        $this->assertEquals('1', $options->get('v'));
    }

    public function test_has()
    {
        $options = new Options(['-r']);
        $this->assertTrue($options->has('recursive:r'));
        $this->assertFalse($options->has('all:a'));

        $options = new Options(['-r', '-ab']);
        $this->assertTrue($options->has('recursive:r'));
        $this->assertFalse($options->has('all:a'));

        $options = new Options(['--recursive', '-ab']);
        $this->assertTrue($options->has('recursive:r'));
        $this->assertFalse($options->has('all:a'));

        $options = new Options(['/']);
        $this->assertFalse($options->has('v'));

        $options = new Options(['/?v=']);
        $this->assertTrue($options->has('v'));
    }
}