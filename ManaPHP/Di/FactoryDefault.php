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
                'alias'=>'ManaPHP\Alias',
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
                'flash' => 'ManaPHP\Flash\Adapter\Direct',
                'flashSession' => 'ManaPHP\Flash\Adapter\Session',
                'tag' => 'ManaPHP\Mvc\View\Tag',
                'session' => 'ManaPHP\Http\Session\Adapter\File',
                'sessionBag' => ['ManaPHP\Http\Session\Bag', false],
                'assets' => 'ManaPHP\Assets\Manager',
                'loader' => 'ManaPHP\Loader',
                'view' => 'ManaPHP\Mvc\View',
                'logger' => 'ManaPHP\Log\Logger',
                'renderer' => 'ManaPHP\Mvc\View\Renderer',
                'debugger' => 'ManaPHP\Debugger',
                'password' => 'ManaPHP\Security\Password',
                'serializer' => 'ManaPHP\Serializer\Adapter\JsonPhp',
                'cache' => 'ManaPHP\Cache\Adapter\File',
                'store' => 'ManaPHP\Store\Adapter\File',
                'counter' => 'ManaPHP\Counter\Adapter\Redis',
                'httpClient'=>'ManaPHP\Http\Client\Adapter\Curl',
            ];

            $this->_aliases=[
                'modelsCache'=>'cache',
                'viewsCache'=>'cache'
            ];
        }
    }
}
