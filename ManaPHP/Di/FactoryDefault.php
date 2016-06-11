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
                'router' => 'ManaPHP\Mvc\Router',
                'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
                'url' => 'ManaPHP\Mvc\Url',
                'modelsManager' => 'ManaPHP\Mvc\Model\Manager',
                'modelsMetadata' => 'ManaPHP\Mvc\Model\MetaData\Memory',
                'response' => 'ManaPHP\Http\Response',
                'cookies' => 'ManaPHP\Http\Cookies',
                'request' => 'ManaPHP\Http\Request',
                'filter' => 'ManaPHP\Filter',
                'escaper' => 'ManaPHP\Escaper',
                'security' => 'ManaPHP\Security',
                'crypt' => 'ManaPHP\Security\Crypt',
                'annotations' => 'ManaPHP\Annotations\Adapter\Memory',
                'flash' => 'ManaPHP\Flash\Direct',
                'flashSession' => 'ManaPHP\Flash\Session',
                'tag' => 'ManaPHP\Mvc\View\Tag',
                'session' => 'ManaPHP\Http\Session\Adapter\File',
                'sessionBag' => ['ManaPHP\Http\Session\Bag', false],
                'eventsManager' => 'ManaPHP\Events\Manager',
                'transactionManager' => 'ManaPHP\Mvc\Model\Transaction\Manager',
                'assets' => 'ManaPHP\Assets\Manager',
                'loader' => 'ManaPHP\Loader',
                'view' => 'ManaPHP\Mvc\View',
                'logger' => 'ManaPHP\Log\Logger',
                'renderer' => 'ManaPHP\Mvc\View\Renderer',
                'debugger' => 'ManaPHP\Debugger',
                'password' => 'ManaPHP\Security\Password'
            ];
        }

    }
}
