<?php

namespace ManaPHP\Mvc;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge(
            $this->_definitions, [
                'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
                'view'         => 'ManaPHP\Mvc\View',
                'flash'        => 'ManaPHP\Mvc\View\Flash\Adapter\Direct',
                'flashSession' => 'ManaPHP\Mvc\View\Flash\Adapter\Session',
                'viewsCache'   => ['ManaPHP\Caching\Cache\Adapter\Redis', 'prefix' => 'cache:views:'],
                'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Session',

                'viewCommand' => 'ManaPHP\Mvc\View\Command',
            ]
        );
    }
}