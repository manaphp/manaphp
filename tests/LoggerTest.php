<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Logger;
use ManaPHP\Logger\Appender\Memory;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function setUp()
    {
        new FactoryDefault();
    }

    public function test_debug()
    {
        $appender =new Memory();

        $logger = new Logger($appender);

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_DEBUG, $log['level']);
        $this->assertContains('**debug**', $log['message']);
    }

    public function test_info()
    {
        $appender =new Memory();

        $logger = new Logger($appender);

        $logger->info('**info**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_INFO, $log['level']);
        $this->assertContains('**info**', $log['message']);
    }

    public function test_warn()
    {
        $appender=new Memory();
        $logger = new Logger($appender);

        $logger->warn('**warning**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_WARN, $log['level']);
        $this->assertContains('**warning**', $log['message']);
    }

    public function test_error()
    {
        $appender = new Memory();
        $logger = new Logger($appender);
        $logger->error('**error**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_ERROR, $log['level']);
        $this->assertContains('**error**', $log['message']);
    }

    public function test_fatal()
    {
        $appender = new Memory();
        $logger = new Logger($appender);

        $logger->fatal('**fatal**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_FATAL, $log['level']);
        $this->assertContains('**fatal**', $log['message']);
    }
}