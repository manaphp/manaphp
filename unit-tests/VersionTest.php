<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/13
 * Time: 21:45
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class VersionTest extends TestCase
{
    public function test_get()
    {
        $version = \ManaPHP\Version::get();
        $this->assertTrue(is_string($version));
        $this->assertRegExp('/\d+\.\d+\.\d+/', $version);
    }
}