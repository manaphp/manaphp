<?php

namespace App\Commands;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface    $logger
 * @property-read \ManaPHP\Redis\RedisDbInterface     $redisDb
 * @property-read \ManaPHP\Redis\RedisCacheInterface  $redisCache
 * @property-read \ManaPHP\Redis\RedisBrokerInterface $redisBroker
 */
class Command extends \ManaPHP\Cli\Command
{

}