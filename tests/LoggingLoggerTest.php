<?php

namespace Tests;

use ManaPHP\Logging\Logger;
use ManaPHP\Logging\Logger\Adapter\Memory;
use ManaPHP\Mvc\Factory;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;

class LoggingLoggerTest extends TestCase
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Factory();
    }

    public function test_debug()
    {
        $logger = $this->container->make(Memory::class);
        $logger->setLevel(Logger::LEVEL_DEBUG);

        $logger->debug('**debug**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->getLogs());
        $log = $logger->getLogs()[0];
        $this->assertEquals('debug', $log->level);
        $this->assertContains('**debug**', $log->message);
    }

    public function test_info()
    {
        $logger = $this->container->make(Memory::class);
        $logger->setLevel(Logger::LEVEL_DEBUG);

        $logger->info('**info**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->getLogs());
        $log = $logger->getLogs()[0];
        $this->assertEquals('info', $log->level);
        $this->assertContains('**info**', $log->message);
    }

    public function test_warn()
    {
        $logger = $this->container->make(Memory::class);
        $logger->setLevel(Logger::LEVEL_DEBUG);

        $logger->warn('**warning**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->getLogs());
        $log = $logger->getLogs()[0];
        $this->assertEquals('warn', $log->level);
        $this->assertContains('**warning**', $log->message);
    }

    public function test_error()
    {
        $logger = $this->container->make(Memory::class);

        $logger->error('**error**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->getLogs());
        $log = $logger->getLogs()[0];
        $this->assertEquals('error', $log->level);
        $this->assertContains('**error**', $log->message);
    }

    public function test_fatal()
    {
        $logger = $this->container->make(Memory::class);

        $logger->fatal('**fatal**');

        // To confirm the debug message correctly
        $this->assertCount(1, $logger->getLogs());
        $log = $logger->getLogs()[0];
        $this->assertEquals('fatal', $log->level);
        $this->assertContains('**fatal**', $log->message);
    }

    public function test_formatMessage()
    {
        $logger = new Memory();

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