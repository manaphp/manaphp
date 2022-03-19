<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Data\RedisDbInterface         $redisDb
 * @property-read \ManaPHP\Data\RedisCacheInterface      $redisCache
 * @property-read \ManaPHP\Data\RedisBrokerInterface     $redisBroker
 * @property-read \ManaPHP\Mvc\ViewInterface             $view
 */
class Controller extends \ManaPHP\Mvc\Controller
{

}