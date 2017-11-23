<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

class SecurityRateLimiterTest extends TestCase
{
    public function test_construct()
    {
        //default
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $rateLimiter = new RateLimiter();
        $rateLimiter->setDependencyInjector($di);

        $this->assertAttributeSame(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $rateLimiter->limit('xx', 1, 10, 1);
        $this->assertAttributeInstanceOf(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $this->assertAttributeSame('', '_prefix', $rateLimiter);

        //instance
        $di = new FactoryDefault();
        $di->alias->set('@data', __DIR__ . '/tmp');

        $file = new RateLimiter\Engine\Redis();
        $rateLimiter = new RateLimiter($file);
        $this->assertAttributeSame($file, '_engine', $rateLimiter);
        $this->assertAttributeSame('', '_prefix', $rateLimiter);

        //class name string
        $rateLimiter = new RateLimiter(RateLimiter\Engine\Redis::class);
        $rateLimiter->setDependencyInjector($di);

        $this->assertAttributeSame(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $rateLimiter->limit('xx', 1, 10, 1);
        $this->assertAttributeInstanceOf(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $this->assertAttributeSame('', '_prefix', $rateLimiter);

        //component name string
        $di->setShared('rateLimiterEngine', RateLimiter\Engine\Redis::class);
        $rateLimiter = new RateLimiter('rateLimiterEngine');
        $rateLimiter->setDependencyInjector($di);

        $this->assertAttributeSame('rateLimiterEngine', '_engine', $rateLimiter);
        $rateLimiter->limit('xx', 1, 10, 1);
        $this->assertAttributeInstanceOf(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $this->assertAttributeSame('', '_prefix', $rateLimiter);

        //array
        $rateLimiter = new RateLimiter(['engine' => RateLimiter\Engine\Redis::class, 'prefix' => 'AAA']);
        $rateLimiter->setDependencyInjector($di);

        $this->assertAttributeSame(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $rateLimiter->limit('xx', 1, 10, 1);
        $this->assertAttributeInstanceOf(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);
        $this->assertAttributeSame('AAA', '_prefix', $rateLimiter);

        //array
        $rateLimiter = new RateLimiter(['engine' => ['class' => RateLimiter\Engine\Redis::class]]);
        $rateLimiter->setDependencyInjector($di);

        $this->assertAttributeSame(['class' => RateLimiter\Engine\Redis::class], '_engine', $rateLimiter);
        $rateLimiter->limit('xx', 1, 10, 1);
        $this->assertAttributeInstanceOf(RateLimiter\Engine\Redis::class, '_engine', $rateLimiter);

        $this->assertAttributeSame('', '_prefix', $rateLimiter);
    }

    public function test_limit()
    {
        $di = new FactoryDefault();
        $rateLimiter = $di->getShared('ManaPHP\Security\RateLimiter');
        $this->assertEquals(9, $rateLimiter->limit('test', 1, 10, 2));
        $this->assertEquals(8, $rateLimiter->limit('test', 1, 10, 2));
        sleep(3);
        $this->assertEquals(9, $rateLimiter->limit('test', 1, 10, 2));
    }

    public function test_limitIp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $di = new FactoryDefault();
        $rateLimiter = $di->getShared('ManaPHP\Security\RateLimiter');
        $this->assertEquals(9, $rateLimiter->limitIp(10, 2));
        $this->assertEquals(8, $rateLimiter->limitIp(10, 2));
        sleep(3);
        $this->assertEquals(9, $rateLimiter->limitIp(10, 2));
    }

    public function test_limitUser()
    {
        $di = new FactoryDefault();
        $rateLimiter = $di->getShared('ManaPHP\Security\RateLimiter');
        $di->setShared('userIdentity', ['userName' => 'manaphp']);

        $this->assertEquals(9, $rateLimiter->limitUser(10, 2));
        $this->assertEquals(8, $rateLimiter->limitUser(10, 2));
        sleep(3);
        $this->assertEquals(9, $rateLimiter->limitUser(10, 2));
    }
}