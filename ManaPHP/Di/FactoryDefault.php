<?php

namespace ManaPHP\Di {

    use ManaPHP\Di;

    /**
     * ManaPHP\Di\FactoryDefault
     *
     * This is a variant of the standard ManaPHP\Di. By default it automatically
     * registers all the services provided by the framework. Thanks to this, the developer does not need
     * to register each service individually providing a full stack framework
     */
    class FactoryDefault extends Di
    {
        /**
         * \ManaPHP\Di\FactoryDefault constructor
         */
        public function __construct()
        {
            parent::__construct();

            $this->_services = [
                'router' => ['ManaPHP\Mvc\Router', true],
                'dispatcher' => ['ManaPHP\Mvc\Dispatcher', true],
                'url' => ['ManaPHP\Mvc\Url', true],
                'modelsManager' => ['ManaPHP\Mvc\Model\Manager', true],
                'modelsMetadata' => ['ManaPHP\Mvc\Model\MetaData\Memory', true],
                'response' => ['ManaPHP\Http\Response', true],
                'cookies' => ['ManaPHP\Http\Cookies', true],
                'request' => ['ManaPHP\Http\Request', true],
                'filter' => ['ManaPHP\Filter', true],
                'escaper' => ['ManaPHP\Escaper', true],
                'security' => ['ManaPHP\Security', true],
                'crypt' => ['ManaPHP\Security\Crypt', true],
                'annotations' => ['ManaPHP\Annotations\Adapter\Memory', true],
                'flash' => ['ManaPHP\Flash\Direct', true],
                'flashSession' => ['ManaPHP\Flash\Session', true],
                'tag' => ['ManaPHP\Mvc\View\Tag', true],
                'session' => ['ManaPHP\Http\Session\Adapter\File', true],
                'sessionBag' => ['ManaPHP\Http\Session\Bag', false],
                'eventsManager' => ['ManaPHP\Events\Manager', true],
                'transactionManager' => ['ManaPHP\Mvc\Model\Transaction\Manager', true],
                'assets' => ['ManaPHP\Assets\Manager', true],
                'loader' => ['ManaPHP\Loader', true],
                'view' => ['ManaPHP\Mvc\View', true],
                'logger' => ['ManaPHP\Log\Logger', true],
                'renderer' => ['ManaPHP\Mvc\View\Renderer', true],
                'debugger' => ['ManaPHP\Debugger', true],
                'password' => ['ManaPHP\Security\Password', true],
            ];
        }

    }
}
