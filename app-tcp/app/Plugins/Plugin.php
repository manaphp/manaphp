<?php

namespace App\Plugins;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \Redis                                 $redisDb
 * @property-read \Redis                                 $redisCache
 * @property-read \Redis                                 $redisBroker
 */
class Plugin extends \ManaPHP\Plugin
{

}