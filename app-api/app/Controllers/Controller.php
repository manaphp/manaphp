<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Redis\RedisDbInterface        $redisDb
 * @property-read \ManaPHP\Redis\RedisCacheInterface     $redisCache
 * @property-read \ManaPHP\Redis\RedisBrokerInterface    $redisBroker
 */
class Controller extends \ManaPHP\Rest\Controller
{

}
