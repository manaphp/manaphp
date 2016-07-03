<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/18
 */
namespace ManaPHP {

    use ManaPHP\Event\Manager;

    /**
     * ManaPHP\Component
     *
     * @property \ManaPHP\Alias                         $alias
     * @property \ManaPHP\Mvc\Dispatcher                $dispatcher
     * @property \ManaPHP\Mvc\Router                    $router
     * @property \ManaPHP\Mvc\Url                       $url
     * @property \ManaPHP\Http\Request                  $request
     * @property \ManaPHP\Http\Filter                   $filter
     * @property \ManaPHP\Http\Response                 $response
     * @property \ManaPHP\Http\Cookies                  $cookies
     * @property \ManaPHP\Mvc\View\Flash                $flash
     * @property \ManaPHP\Mvc\View\Flash                $flashSession
     * @property \ManaPHP\Http\SessionInterface         $session
     * @property \ManaPHP\Event\ManagerInterface        $eventsManager
     * @property \ManaPHP\Db                            $db
    //* @property \ManaPHP\Security $security
     * @property \ManaPHP\Security\Crypt                $crypt
     * @property \ManaPHP\Mvc\Model\Manager             $modelsManager
     * @property \ManaPHP\Mvc\Model\Metadata            $modelsMetadata
    //     * @property \ManaPHP\Assets\Manager $assets
     * @property \ManaPHP\Di|\ManaPHP\DiInterface       $di
     * @property \ManaPHP\Http\Session\Bag              $persistent
     * @property \ManaPHP\Mvc\View                      $view
     * @property \ManaPHP\Mvc\View\Tag                  $tag
     * @property \ManaPHP\Loader                        $loader
     * @property \ManaPHP\Log\Logger                    $logger
     * @property \ManaPHP\Mvc\View\Renderer             $renderer
     * @property \Application\Configure                 $configure
     * @property \ManaPHP\ApplicationInterface          $application
     * @property \ManaPHP\Debugger                      $debugger
     * @property \ManaPHP\Authentication\Password       $password
     * @property \Redis                                 $redis
     * @property \ManaPHP\Serializer\AdapterInterface   $serializer
     * @property \ManaPHP\Cache                         $cache
     * @property \ManaPHP\Store                         $store
     * @property \ManaPHP\Counter                       $counter
     * @property \ManaPHP\CacheInterface                $viewsCache
     * @property \ManaPHP\Http\Client                   $httpClient
     * @property \ManaPHP\AuthorizationInterface        $authorization
     * @property \ManaPHP\Security\Captcha              $captcha
     * @property \ManaPHP\Security\CsrfToken            $csrfToken
     * @property \ManaPHP\Authentication\UserIdentifier $userIdentifier
     */
    class Component implements ComponentInterface
    {
        /**
         * @var \ManaPHP\Event\Manager
         */
        protected $_eventsManager;

        /**
         * @var array
         */
        private static $_eventPeeks;

        /**
         * @var \ManaPHP\Di
         */
        protected $_dependencyInjector;

        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();
        }

        /**
         * Sets the dependency injector
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @return static
         */
        public function setDependencyInjector($dependencyInjector)
        {
            $this->_dependencyInjector = $dependencyInjector;

            return $this;
        }

        /**
         * Returns the internal dependency injector
         *
         * @return \ManaPHP\DiInterface
         */
        public function getDependencyInjector()
        {
            return $this->_dependencyInjector;
        }

        /** @noinspection MagicMethodsValidityInspection */
        /**
         * Magic method __get
         *
         * @param string $propertyName
         *
         * @return mixed
         */
        public function __get($propertyName)
        {
            if ($this->_dependencyInjector->has($propertyName)) {
                return $this->{$propertyName} = $this->_dependencyInjector->getShared($propertyName);
            }

            if ($propertyName === 'di') {
                return $this->{'di'} = $this->_dependencyInjector;
            }

            if ($propertyName === 'persistent') {
                return $this->{'persistent'} = $this->_dependencyInjector->get('sessionBag', [get_class($this), $this->_dependencyInjector]);
            }

            trigger_error('Access to undefined property ' . $propertyName);

            return null;
        }

        /**
         * Attach a listener to the events manager
         *
         * @param string                                    $event
         * @param callable|\ManaPHP\Event\ListenerInterface $handler
         *
         * @return static
         * @throws \ManaPHP\Event\Exception
         */
        public function attachEvent($event, $handler)
        {
            if ($this->_eventsManager === null) {
                $this->_eventsManager = new Manager();
            }

            $this->_eventsManager->attachEvent($event, $handler);

            return $this;
        }

        /**
         * Fires an event in the events manager causing that the active listeners will be notified about it
         *
         * @param string $event
         * @param mixed  $data
         *
         * @return mixed
         */
        public function fireEvent($event, $data = null)
        {
            if (self::$_eventPeeks !== null) {
                foreach (self::$_eventPeeks as $peek) {
                    $peek($event, $this, $data);
                }
            }

            if ($this->_eventsManager !== null) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                return $this->_eventsManager->fireEvent($event, $this, $data);
            }

            return null;
        }

        /**
         * @param \Closure $peek
         *
         * @throws Exception
         */
        public static function peekEvents($peek)
        {
            if (self::$_eventPeeks === null) {
                self::$_eventPeeks = [$peek];
            } else {
                self::$_eventPeeks[] = $peek;
            }
        }

        /**
         * @param string $property
         *
         * @return bool
         */
        public function hasProperty($property)
        {
            return array_key_exists($property, get_object_vars($this));
        }

        /**
         * @param string $property
         * @param mixed  $value
         *
         * @return mixed
         * @throws \ManaPHP\Exception
         */
        public function setProperty($property, $value)
        {
            if (array_key_exists($property, get_object_vars($this))) {
                $old = $this->{$property};
                $this->{$property} = $value;
                return $old;
            } else {
                throw new Exception("property '$property' is not exists in " . get_class($this));
            }
        }

        /**
         * @param string $property
         *
         * @return mixed
         * @throws \ManaPHP\Exception
         */
        public function getProperty($property)
        {
            if (array_key_exists($property, get_object_vars($this))) {
                return $this->{$property};
            } else {
                throw new Exception("property '$property' is not exists in " . get_class($this));
            }
        }

        /**
         * @return array
         */
        public function getProperties()
        {
            return get_object_vars($this);
        }

        public function __debugInfo()
        {
            $defaultDi = Di::getDefault();

            $data = [];
            foreach (get_object_vars($this) as $k => $v) {

                if ($v === $defaultDi) {
                    continue;
                }

                $data[$k] = $v;
            }

            return $data;
        }

        public function dump()
        {
            $data = [];

            foreach (get_object_vars($this) as $k => $v) {
                if ($k === '_eventsManager') {
                    continue;
                }

                if (is_scalar($v) || $v === null) {
                    $data[$k] = $v;
                } elseif (is_array($v)) {
                    $isPlain = true;

                    foreach ($v as $vv) {
                        if (!is_scalar($vv) && $vv !== null) {
                            $isPlain = false;
                            break;
                        }
                    }

                    if ($isPlain) {
                        $data[$k] = $v;
                    }
                }
            }

            return $data;
        }
    }
}