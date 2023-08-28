<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

class CallInPoolException extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct(['`Redis::{1}` call is not supported in pool.', $method]);
    }
}