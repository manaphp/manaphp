<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class LoggerTest extends TestCase
{
    public function setUp()
    {
        new \ManaPHP\Di\FactoryDefault();
    }

    public function test_setLevel()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());

        // To confirm the default level is LEVEL_ALL
        $this->assertEquals(ManaPHP\Logger::LEVEL_ALL, $logger->getLevel());

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_DEBUG, $log['level']);
        $this->assertContains('**debug**', $log['message']);
        $this->assertTrue(isset($log['context']));

        // To confirm the level can set correctly
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());
        $logger->setLevel(ManaPHP\Logger::LEVEL_OFF);
        $this->assertEquals(ManaPHP\Logger::LEVEL_OFF, $logger->getLevel());

        $logger->debug('**debug**');

        // To confirm  when LEVEL is higher than log level, the log was ignored correctly
        $this->assertCount(0, $logger->adapter->getLogs());
    }

    public function test_getLevel()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());
        $logger->setLevel(ManaPHP\Logger::LEVEL_INFO);
        $this->assertEquals(ManaPHP\Logger::LEVEL_INFO, $logger->getLevel());
    }

    public function test_debug()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_DEBUG, $log['level']);
        $this->assertContains('**debug**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_info()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());

        $logger->info('**info**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_INFO, $log['level']);
        $this->assertContains('**info**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_warning()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());

        $logger->warning('**warning**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_WARNING, $log['level']);
        $this->assertContains('**warning**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_error()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());
        $logger->error('**error**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_ERROR, $log['level']);
        $this->assertContains('**error**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }

    public function test_fatal()
    {
        $logger = new ManaPHP\Logger(new \ManaPHP\Logger\Adapter\Memory());

        $logger->fatal('**fatal**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->adapter->getLogs());
        $log = $logger->adapter->getLogs()[0];
        $this->assertEquals(ManaPHP\Logger::LEVEL_FATAL, $log['level']);
        $this->assertContains('**fatal**', $log['message']);
        $this->assertTrue(isset($log['context']));
    }
}