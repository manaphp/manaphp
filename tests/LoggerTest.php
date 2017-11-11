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

    public function test_setLevel()
    {
        $logger = new Logger(new Memory());

        // To confirm the default level is LEVEL_ALL
        $this->assertEquals(Logger::LEVEL_DEBUG, $logger->getLevel());

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_DEBUG, $log['level']);
        $this->assertContains('**debug**', $log['message']);
        $this->assertTrue(isset($log['context']));

        // To confirm the level can set correctly
        $logger = new Logger(new Memory());
        $logger->setLevel(Logger::LEVEL_ERROR);
        $this->assertEquals(Logger::LEVEL_ERROR, $logger->getLevel());

        $logger->debug('**debug**');

        // To confirm  when LEVEL is higher than log level, the log was ignored correctly
        $this->assertCount(0, $logger->adapter->getLogs());
    }

    public function test_getLevel()
    {
        $logger = new Logger(new Memory());
        $logger->setLevel(Logger::LEVEL_INFO);
        $this->assertEquals(Logger::LEVEL_INFO, $logger->getLevel());
    }

    public function test_debug()
    {
        $logger = new Logger(new Memory());

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_DEBUG, $log['level']);
        $this->assertContains('**debug**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_info()
    {
        $logger = new Logger(new Memory());

        $logger->info('**info**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_INFO, $log['level']);
        $this->assertContains('**info**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_warn()
    {
        $logger = new Logger(new Memory());

        $logger->warn('**warning**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_WARN, $log['level']);
        $this->assertContains('**warning**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_error()
    {
        $logger = new Logger(new Memory());
        $logger->error('**error**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_ERROR, $log['level']);
        $this->assertContains('**error**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_fatal()
    {
        $logger = new Logger(new Memory());

        $logger->fatal('**fatal**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_FATAL, $log['level']);
        $this->assertContains('**fatal**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }
}