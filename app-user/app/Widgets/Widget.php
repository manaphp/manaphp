<?php

namespace App\Widgets;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Redis\RedisDbInterface        $redisDb
 * @property-read \ManaPHP\Redis\RedisCacheInterface     $redisCache
 * @property-read \ManaPHP\Redis\RedisBrokerInterface    $redisBroker
 */
abstract class Widget extends \ManaPHP\Mvc\View\Widget
{

}
