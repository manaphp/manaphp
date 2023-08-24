<?php

namespace App\Widgets;

use ManaPHP\Mvc\View\WidgetInterface;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Redis\RedisDbInterface        $redisDb
 * @property-read \ManaPHP\Redis\RedisCacheInterface     $redisCache
 * @property-read \ManaPHP\Redis\RedisBrokerInterface    $redisBroker
 */
abstract class Widget implements WidgetInterface
{

}
