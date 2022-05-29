<?php
declare(strict_types=1);

namespace App\Commands;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface   $logger
 * @property-read \ManaPHP\Data\RedisDbInterface     $redisDb
 * @property-read \ManaPHP\Data\RedisCacheInterface  $redisCache
 * @property-read \ManaPHP\Data\RedisBrokerInterface $redisBroker
 */
class Command extends \ManaPHP\Cli\Command
{

}