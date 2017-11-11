<?php

namespace ManaPHP;

use ManaPHP\Di\Exception as DiException;

/**
 * Class ManaPHP\Di
 *
 * @package  di
 *
 * @property \ManaPHP\AliasInterface                       $alias
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Mvc\RouterInterface                  $router
 * @property \ManaPHP\Mvc\UrlInterface                     $url
 * @property \ManaPHP\Http\RequestInterface                $request
 * @property \ManaPHP\Http\FilterInterface                 $filter
 * @property \ManaPHP\Http\ResponseInterface               $response
 * @property \ManaPHP\Http\CookiesInterface                $cookies
 * @property \ManaPHP\Mvc\View\FlashInterface              $flash
 * @property \ManaPHP\Mvc\View\FlashInterface              $flashSession
 * @property \ManaPHP\Http\SessionInterface                $session
 * @property \ManaPHP\Event\ManagerInterface               $eventsManager
 * @property \ManaPHP\DbInterface                          $db
 * @property \ManaPHP\Security\CryptInterface              $crypt
 * @property \ManaPHP\Db\Model\MetadataInterface           $modelsMetadata
 * @property \ManaPHP\Cache\AdapterInterface               $modelsCache
 * @property \ManaPHP\Di|\ManaPHP\DiInterface              $di
 * @property \ManaPHP\Http\Session\Bag                     $persistent
 * @property \ManaPHP\Mvc\ViewInterface                    $view
 * @property \ManaPHP\Loader                               $loader
 * @property \ManaPHP\LoggerInterface                      $logger
 * @property \ManaPHP\RendererInterface                    $renderer
 * @property \ManaPHP\Configure                            $configure
 * @property \ManaPHP\ApplicationInterface                 $application
 * @property \ManaPHP\DebuggerInterface                    $debugger
 * @property \ManaPHP\Authentication\PasswordInterface     $password
 * @property \ManaPHP\Redis                                $redis
 * @property \ManaPHP\Serializer\AdapterInterface          $serializer
 * @property \ManaPHP\CacheInterface                       $cache
 * @property \ManaPHP\CounterInterface                     $counter
 * @property \ManaPHP\Cache\AdapterInterface               $viewsCache
 * @property \ManaPHP\Http\ClientInterface                 $httpClient
 * @property \ManaPHP\AuthorizationInterface               $authorization
 * @property \ManaPHP\Security\CaptchaInterface            $captcha
 * @property \ManaPHP\Security\CsrfTokenInterface          $csrfToken
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Paginator                            $paginator
 * @property \ManaPHP\FilesystemInterface                  $filesystem
 * @property \ManaPHP\Security\RandomInterface             $random
 * @property \ManaPHP\Message\QueueInterface               $messageQueue
 * @property \ManaPHP\Text\CrosswordInterface              $crossword
 * @property \ManaPHP\Security\RateLimiterInterface        $rateLimiter
 * @property \ManaPHP\Meter\LinearInterface                $linearMeter
 * @property \ManaPHP\Meter\RoundInterface                 $roundMeter
 * @property \ManaPHP\Security\SecintInterface             $secint
 * @property \ManaPHP\I18n\Translation                     $translation
 * @property \ManaPHP\Renderer\Engine\Sword\Compiler       $swordCompiler
 * @property \ManaPHP\StopwatchInterface                   $stopwatch
 * @property \ManaPHP\Security\HtmlPurifierInterface       $htmlPurifier
 * @property \ManaPHP\Cli\EnvironmentInterface             $environment
 * @property \ManaPHP\Net\ConnectivityInterface            $netConnectivity
 */
class Di implements DiInterface
{

    /**
     * @var array
     */
    protected $_components = [];

    /**
     * @var array
     */
    protected $_aliases = [];

    /**
     * @var array
     */
    protected $_sharedInstances = [];

    /**
     * First DI build
     *
     * @var \ManaPHP\Di
     */
    protected static $_default;

    public function __construct()
    {
        if (self::$_default === null) {
            self::$_default = $this;
        }
    }

    /**
     * Return the First DI created
     *
     * @return static
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Registers a component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($name, $definition)
    {
        if (is_object($definition) && !$definition instanceof \Closure) {
            $definition = ['class' => $definition, 'shared' => true];
        } elseif (!is_array($definition)) {
            $definition = ['class' => $definition, 'shared' => false];
        } else {
            if (!isset($definition['shared'])) {
                $definition['shared'] = false;
            }
        }

        $this->_components[$name] = $definition;

        return $this;
    }

    /**
     * Registers an "always shared" component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function setShared($name, $definition)
    {
        if (isset($this->_components[$name]) && is_array($definition) && !isset($definition['class'])) {
            $component = $this->_components[$name];
            $definition['class'] = is_string($component) ? $component : $component['class'];
        }
        $this->_components[$name] = $definition;

        return $this;
    }

    /**
     * @param string       $component
     * @param string|array $aliases
     * @param bool         $force
     *
     * @return static
     */
    public function setAliases($component, $aliases, $force = false)
    {
        if (is_string($aliases)) {
            if ($force || !isset($this->_aliases[$aliases])) {
                $this->_aliases[$aliases] = $component;
            }
        } else {
            /** @noinspection ForeachSourceInspection */
            foreach ($aliases as $alias) {
                if ($force || !isset($this->_aliases[$alias])) {
                    $this->_aliases[$alias] = $component;
                }
            }
        }

        return $this;
    }

    /**
     * Removes a component in the components container
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name)
    {
        if (in_array($name, $this->_aliases, true)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new DiException('`:name` component is being used by alias, please remove alias first'/**m04c19e730f00d1a9f*/, ['name' => $name]);
        }

        if (isset($this->_aliases[$name])) {
            unset($this->_aliases[$name]);
        } else {
            unset($this->_components[$name], $this->_sharedInstances[$name], $this->{$name});
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $definition
     * @param array  $parameters
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \ManaPHP\Di\Exception
     */
    protected function _getInstance($name, $definition, $parameters)
    {
        if (is_string($definition)) {
            if (!class_exists($definition)) {
                throw new DiException('`:name` component cannot be resolved: `:class` class is not exists'/**m03ae8f20fcb7c5ba6*/, ['name' => $name, 'class' => $definition]);
            }
            $count = count($parameters);

            if ($count === 0) {
                $instance = new $definition();
            } elseif ($count === 1) {
                $instance = new $definition($parameters[0]);
            } elseif ($count === 2) {
                $instance = new $definition($parameters[0], $parameters[1]);
            } elseif ($count === 3) {
                $instance = new $definition($parameters[0], $parameters[1], $parameters[2]);
            } else {
                $reflection = new \ReflectionClass($definition);
                $instance = $reflection->newInstanceArgs($parameters);
            }
        } elseif ($definition instanceof \Closure) {
            $instance = call_user_func_array($definition, $parameters);
        } elseif (is_object($definition)) {
            $instance = $definition;
        } else {
            throw new DiException('`:name` component cannot be resolved: component implement type is not supported'/**m072d42756355fb069*/, ['name' => $name]);
        }

        return $instance;
    }

    /**
     * Resolves the component based on its configuration
     *
     * @param string $_name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function get($_name, $parameters = null)
    {
        $name = (!isset($this->_components[$_name]) && isset($this->_aliases[$_name])) ? $this->_aliases[$_name] : $_name;

        if (isset($this->_sharedInstances[$name])) {
            return $this->_sharedInstances[$name];
        }

        if (isset($this->_components[$name])) {
            $component = $this->_components[$name];

            $shared = $parameters === null;
            if (is_string($component)) {
                $definition = $component;
            } elseif (is_array($component) && isset($component['class'])) {
                $definition = $component['class'];
                unset($component['class']);

                if (isset($component['shared'])) {
                    $shared = $component['shared'];
                    unset($component['shared']);
                }

                if ($parameters === null && count($component) !== 0) {
                    $parameters = isset($component[0]) ? $component : [$component];
                }
            } else {
                $definition = $component;
                $shared = true;
            }
        } else {
            $definition = $name;
            $shared = false;
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $instance = $this->_getInstance($_name, $definition, $parameters ?: []);

        if ($shared) {
            $this->_sharedInstances[$name] = $instance;
        }

        if ($instance instanceof Component) {
            $instance->setDependencyInjector($this);
        }

        return $instance;
    }

    /**
     * Resolves a component, the resolved component is stored in the DI, subsequent requests for this component will return the same instance
     *
     * @param string|array $name
     * @param array        $parameters
     *
     * @return mixed
     */
    public function getShared($name, $parameters = null)
    {
        if (is_array($name)) {
            $parameters = $name;
            $name = $name['class'];
            unset($parameters['class']);
            if (!isset($parameters[0])) {
                $parameters = [$parameters];
            }
        }

        if (!isset($this->_sharedInstances[$name])) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_sharedInstances[$name] = $this->get($name, $parameters);
        }

        return $this->_sharedInstances[$name];
    }

    /**
     * Magic method __get
     *
     * @param string $propertyName
     *
     * @return mixed
     * @throws \ManaPHP\Di\Exception
     */
    public function __get($propertyName)
    {
        return $this->getShared($propertyName);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws \ManaPHP\Di\Exception
     */
    public function __set($name, $value)
    {
        if ($value === null) {
            $this->remove($name);
        } else {
            $this->setShared($name, $value);
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Check whether the DI contains a component by a name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_components[$name]) || isset($this->_aliases[$name]);
    }

    /**
     * Magic method to get or set components using setters/getters
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return void
     * @throws \ManaPHP\Di\Exception
     */
    public function __call($method, $arguments = [])
    {
        throw new DiException('Call to undefined method `:method`'/**m06946faf1ec42dea1*/, ['method' => $method]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return get_object_vars($this);
    }

    public function reConstruct()
    {
        foreach ($this->_sharedInstances as $k => $v) {
            if ($v instanceof Component) {
                $v->reConstruct();
            }
        }
    }
}
