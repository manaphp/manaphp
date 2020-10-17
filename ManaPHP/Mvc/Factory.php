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
                'view'         => 'ManaPHP\View',
                'flash'        => 'ManaPHP\View\Flash\Adapter\Direct',
                'flashSession' => 'ManaPHP\View\Flash\Adapter\Session',
                'viewsCache'   => ['ManaPHP\Cache\Adapter\Redis', 'prefix' => 'cache:views:'],
                'identity'     => 'ManaPHP\Identity\Adapter\Session',
            ]
        );
    }
}