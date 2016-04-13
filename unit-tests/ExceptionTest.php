<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/13
 * Time: 21:49
 */
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class ExceptionTest extends TestCase
{
    public function test_throw()
    {
        try {
            throw new \ManaPHP\Exception();
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Exception', $e);
        }
    }
}