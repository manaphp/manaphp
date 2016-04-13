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
                'router' => new Service('router', 'ManaPHP\Mvc\Router', true),
                'dispatcher' => new Service('dispatcher', 'ManaPHP\Mvc\Dispatcher', true),
                'url' => new Service('url', 'ManaPHP\Mvc\Url', true),
                'modelsManager' => new Service('modelsManager', 'ManaPHP\Mvc\Model\Manager', true),
                'modelsMetadata' => new Service('modelsMetadata', 'ManaPHP\Mvc\Model\MetaData\Memory', true),
                'response' => new Service('response', 'ManaPHP\Http\Response', true),
                'cookies' => new Service('cookies', 'ManaPHP\Http\Response\Cookies', true),
                'request' => new Service('request', 'ManaPHP\Http\Request', true),
                'filter' => new Service('filter', 'ManaPHP\Filter', true),
                'escaper' => new Service('escaper', 'ManaPHP\Escaper', true),
                'security' => new Service('security', 'ManaPHP\Security', true),
                'crypt' => new Service('crypt', 'ManaPHP\Crypt', true),
                'annotations' => new Service('annotations', 'ManaPHP\Annotations\Adapter\Memory', true),
                'flash' => new Service('flash', 'ManaPHP\Flash\Direct', true),
                'flashSession' => new Service('flashSession', 'ManaPHP\Flash\Session', true),
                'tag' => new Service('tag', 'ManaPHP\Mvc\View\Tag', true),
                'session' => new Service('session', 'ManaPHP\Http\Session\Adapter\File', true),
                'sessionBag' => new Service('sessionBag', 'ManaPHP\Session\Bag', true),
                'eventsManager' => new Service('eventsManager', 'ManaPHP\Events\Manager', true),
                'transactionManager' => new Service('transactionManager', 'ManaPHP\Mvc\Model\Transaction\Manager',
                    true),
                'assets' => new Service('assets', 'ManaPHP\Assets\Manager', true),
                'loader' => new Service('loader', 'ManaPHP\Loader', true),
                'view' => new Service('view', 'ManaPHP\Mvc\View', true),
                'logger' => new Service('logger', 'ManaPHP\Log\Logger', true),
                'renderer' => new Service('renderer', 'ManaPHP\Mvc\View\Renderer', true),
            ];
        }

    }
}
