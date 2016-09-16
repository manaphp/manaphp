<?php

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