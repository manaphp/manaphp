<?php

namespace ManaPHP\Mvc;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'httpHandler'  => 'ManaPHP\Mvc\Handler',
            'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
            'view'         => 'ManaPHP\Mvc\View',
            'flash'        => 'ManaPHP\Mvc\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\Mvc\View\Flash\Adapter\Session',
            'viewsCache'   => ['ManaPHP\Caching\Cache\Adapter\Redis', 'prefix' => 'cache:views:'],
            'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Session',

            'viewCommand' => 'ManaPHP\Mvc\View\Command',
        ];
}