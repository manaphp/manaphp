<?php

namespace App\Widgets;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \Redis                                 $redisDb
 * @property-read \Redis                                 $redisCache
 * @property-read \Redis                                 $redisBroker
 */
abstract class Widget extends \ManaPHP\Mvc\View\Widget
{

}
