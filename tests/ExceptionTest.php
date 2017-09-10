<?php

namespace Tests;

use ManaPHP\Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function test_throw()
    {
        try {
            throw new Exception();
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Exception', $e);
        }
    }
}