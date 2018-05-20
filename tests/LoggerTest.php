<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Logger;
use ManaPHP\Logger\Appender\Memory;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;

class LoggerTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $_di;

    public function setUp()
    {
        $this->_di = new FactoryDefault();
    }

    public function test_construct()
    {
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        $appender = $logger->getAppender(0);
        $this->assertInstanceOf(Memory::class, $appender);

        $logger = $this->_di->getInstance(Logger::class, [[Memory::class]]);
        $appender = $logger->getAppender(0);
        $this->assertInstanceOf(Memory::class, $appender);

        $logger = $this->_di->getInstance(Logger::class, [['memory' => [Memory::class]]]);
        $appender = $logger->getAppender('memory');
        $this->assertInstanceOf(Memory::class, $appender);

        $logger = $this->_di->getInstance(Logger::class, [['memory' => [Memory::class], 'level' => 'DEBUG']]);
        $appender = $logger->getAppender('memory');
        $this->assertInstanceOf(Memory::class, $appender);
    }

    public function test_debug()
    {
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $appender = $logger->getAppender(0);

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_DEBUG, $log->level);
        $this->assertContains('**debug**', $log->message);
    }

    public function test_info()
    {
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $appender = $logger->getAppender(0);

        $logger->info('**info**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_INFO, $log->level);
        $this->assertContains('**info**', $log->message);
    }

    public function test_warn()
    {
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $appender = $logger->getAppender(0);

        $logger->warn('**warning**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_WARN, $log->level);
        $this->assertContains('**warning**', $log->message);
    }

    public function test_error()
    {
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $appender = $logger->getAppender(0);

        $logger->error('**error**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_ERROR, $log->level);
        $this->assertContains('**error**', $log->message);
    }

    public function test_fatal()
    {
        $logger = $this->_di->getInstance(Logger::class, [Memory::class]);
        /**
         * @var \ManaPHP\Logger\Appender\Memory $appender
         */
        $appender = $logger->getAppender(0);

        $logger->fatal('**fatal**');

        // To confirm the debug message correctly
        $this->assertCount(1, $appender->getLogs());
        $log = $appender->getLogs()[0];
        $this->assertEquals(Logger::LEVEL_FATAL, $log->level);
        $this->assertContains('**fatal**', $log->message);
    }

    public function test_formatMessage()
    {
        $logger = new Logger();

        $city = new City();
        $city->city_id = 1;
        $city->city = 'shenzhen';
        $this->assertEquals('city {"city_id":1,"city":"shenzhen"}', $logger->formatMessage(['city :1', $city]));
        $this->assertEquals('[]', $logger->formatMessage([]));
        $this->assertEquals('test', $logger->formatMessage(['test']));
        $this->assertEquals('hello manaphp', $logger->formatMessage(['hello :1', 'manaphp']));
        $this->assertEquals('hello manaphp', $logger->formatMessage(['hello :name', 'name' => 'manaphp']));
        $this->assertEquals('id: [1,2,3,4]', $logger->formatMessage(['id', [1, 2, 3, 4]]));
        $this->assertEquals('id: [1,2,3,4]', $logger->formatMessage(['id: ', [1, 2, 3, 4]]));
        $this->assertEquals('id: 12 => [1,2,3,4]', $logger->formatMessage(['id: ', 12, [1, 2, 3, 4]]));
        $this->assertEquals('{"city_id":1,"city":"shenzhen"}', $logger->formatMessage($city));
        $this->assertEquals('city: {"city_id":1,"city":"shenzhen"}', $logger->formatMessage(['city', $city]));
        $this->assertEquals('city {"city_id":1,"city":"shenzhen"}', $logger->formatMessage(['city :1', $city]));
    }
}