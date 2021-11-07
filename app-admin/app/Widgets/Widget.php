<?php

namespace App\Widgets;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Data\RedisDbInterface         $redisDb
 * @property-read \ManaPHP\Data\RedisCacheInterface      $redisCache
 * @property-read \ManaPHP\Data\RedisBrokerInterface     $redisBroker
 */
abstract class Widget extends \ManaPHP\Mvc\View\Widget
{

}