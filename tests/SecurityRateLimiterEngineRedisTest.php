<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class SecurityRateLimiterEngineRedisTest extends TestCase
{
    public function test_check()
    {
        $di = new FactoryDefault();
        $redis = $di->getShared('ManaPHP\Security\RateLimiter\Engine\Redis');
        $this->assertEquals(1, $redis->check('test', 1, 2));
        $this->assertEquals(2, $redis->check('test', 1, 2));
        sleep(3);
        $this->assertEquals(1, $redis->check('test', 1, 2));
    }
}