<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class CliArgumentsTest extends TestCase
{
    public function test_get()
    {
        $arguments = new \ManaPHP\Cli\Arguments(['-r', 'yes']);
        $this->assertEquals('yes', $arguments->get('recursive:r'));
        $this->assertEquals(null, $arguments->get('all:a'));
    }

    public function test_has()
    {
        $arguments = new \ManaPHP\Cli\Arguments(['-r']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));

        $arguments = new \ManaPHP\Cli\Arguments(['-r', '-ab']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));

        $arguments = new \ManaPHP\Cli\Arguments(['--recursive', '-ab']);
        $this->assertTrue($arguments->has('recursive:r'));
        $this->assertFalse($arguments->has('all:a'));
    }
}