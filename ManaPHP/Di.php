<?php

namespace ManaPHP;

use ManaPHP\Exception\BadMethodCallException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnexpectedValueException;

/**
 * Class ManaPHP\Di
 *
 * @package  di
 *
 * @property-read \ManaPHP\AliasInterface                  $alias
 * @property-read \ManaPHP\DispatcherInterface             $dispatcher
 * @property-read \ManaPHP\RouterInterface                 $router
 * @property-read \ManaPHP\UrlInterface                    $url
 * @property-read \ManaPHP\Http\RequestInterface           $request
 * @property-read \ManaPHP\Http\ResponseInterface          $response
 * @property-read \ManaPHP\Http\CookiesInterface           $cookies
 * @property-read \ManaPHP\View\FlashInterface             $flash
 * @property-read \ManaPHP\View\FlashInterface             $flashSession
 * @property-read \ManaPHP\Http\SessionInterface           $session
 * @property-read \ManaPHP\Event\ManagerInterface          $eventsManager
 * @property-read \ManaPHP\DbInterface                     $db
 * @property-read \ManaPHP\Security\CryptInterface         $crypt
 * @property-read \ManaPHP\Db\Model\MetadataInterface      $modelsMetadata
 * @property-read \ManaPHP\ValidatorInterface              $validator
 * @property-read \ManaPHP\Di|\ManaPHP\DiInterface         $di
 * @property-read \ManaPHP\ViewInterface                   $view
 * @property-read \ManaPHP\Loader                          $loader
 * @property-read \ManaPHP\LoggerInterface                 $logger
 * @property-read \ManaPHP\RendererInterface               $renderer
 * @property-read \ManaPHP\Configuration\Configure         $configure
 * @property-read \ManaPHP\Configuration\SettingsInterface $settings
 * @property-read \Redis                                   $redis
 * @property-read \ManaPHP\CacheInterface                  $cache
 * @property-read \ManaPHP\CacheInterface                  $viewsCache
 * @property-read \ManaPHP\Http\ClientInterface            $httpClient
 * @property-read \ManaPHP\Http\ClientInterface            $restClient
 * @property-read \ManaPHP\AuthorizationInterface          $authorization
 * @property-read \ManaPHP\Security\CaptchaInterface       $captcha
 * @property-read \ManaPHP\IdentityInterface               $identity
 * @property-read \ManaPHP\Paginator                       $paginator
 * @property-read \ManaPHP\FilesystemInterface             $filesystem
 * @property-read \ManaPHP\Security\RandomInterface        $random
 * @property-read \ManaPHP\Message\QueueInterface          $messageQueue
 * @property-read \ManaPHP\Security\SecintInterface        $secint
 * @property-read \ManaPHP\I18n\Translator                 $translation
 * @property-read \ManaPHP\Renderer\Engine\Sword\Compiler  $swordCompiler
 * @property-read \ManaPHP\Security\HtmlPurifierInterface  $htmlPurifier
 * @property-read \ManaPHP\AmqpInterface                   $rabbitmq
 * @property-read \ManaPHP\Model\Relation\Manager          $relationsManager
 * @property-read \ManaPHP\MailerInterface                 $mailer
 * @property-read \ManaPHP\Task\ManagerInterface           $tasksManager
 * @property-read \ManaPHP\Ipc\CacheInterface              $ipcCache
 * @property-read \ManaPHP\MongodbInterface                $mongodb
 * @property-read \ManaPHP\Bos\ClientInterface             $bosClient
 */
class Di implements DiInterface
{
    /**
     * @var array
     */
    protected $_definitions = [];

    /**
     * @var array
     */
    protected $_patterns = [];

    /**
     * @var array
     */
    protected $_instances = [];

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

        $this->_instances['di'] = $this;
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
     * @param string $name
     * @param string $className
     *
     * @return string
     */
    protected function _completeClassName($name, $className)
    {
        if (isset($this->_definitions[$name])) {
            $definition = $this->_definitions[$name];
        } else {
            return $className;
        }

        if (is_string($definition)) {
            if ($pos = strrpos($definition, '\\')) {
                return substr($definition, 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } elseif (is_array($definition) && isset($definition['class'])) {
            if ($pos = strrpos($definition['class'], '\\')) {
                return substr($definition['class'], 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } else {
            return $className;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function _interClassName($name)
    {
        $definition = null;
        if (isset($this->_definitions[$name])) {
            $definition = $this->_definitions[$name];
        } elseif (strpos($name, '\\') !== false) {
            $definition = $name;
        } elseif ($pos = strrpos($name, '_')) {
            $maybe = substr($name, $pos + 1);
            if (isset($this->_definitions[$maybe])) {
                $definition = $this->_definitions[$maybe];
            }
        } elseif (preg_match('#^(.+)([A-Z].+?)$#', $name, $match)) {
            $maybe = lcfirst($match[2]);
            if (isset($this->_definitions[$maybe])) {
                $definition = $this->_definitions[$maybe];
            } else {
                $definition = $this->_getPatterned($name);
            }
        }

        if ($definition === null) {
            throw new InvalidValueException(['`:definition` definition is invalid: missing class field', 'definition' => $name]);
        }

        return is_string($definition) ? $definition : $definition['class'];
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
        if (is_string($definition)) {
            if (strpos($definition, '/') !== false || preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                $definition = ['class' => $this->_interClassName($name), $definition, 'shared' => false];
            } else {
                if (strpos($definition, '\\') === false) {
                    $definition = $this->_completeClassName($name, $definition);
                }
                $definition = ['class' => $definition, 'shared' => false];
            }
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                if (strpos($definition['class'], '\\') === false) {
                    $definition['class'] = $this->_completeClassName($name, $definition['class']);
                }
            } elseif (isset($definition[0]) && count($definition) !== 1) {
                if (strpos($definition[0], '\\') === false) {
                    $definition[0] = $this->_completeClassName($name, $definition[0]);
                }
            } else {
                $definition['class'] = $this->_interClassName($name);
            }

            $definition['shared'] = false;
        } elseif (is_object($definition)) {
            $definition = ['class' => $definition, 'shared' => !$definition instanceof \Closure];
        } else {
            throw new UnexpectedValueException(['`:definition` definition is unknown', 'definition' => $name]);
        }

        $this->_definitions[$name] = $definition;

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
        if (isset($this->_instances[$name])) {
            throw new MisuseException(['it\'s too late to setShared(): `:name` instance has been created', 'name' => $name]);
        }

        if (is_string($definition)) {
            if (strpos($definition, '/') !== false || preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                $definition = ['class' => $this->_interClassName($name), $definition];
            } elseif (strpos($definition, '\\') === false) {
                $definition = $this->_completeClassName($name, $definition);
            }
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                if (strpos($definition['class'], '\\') === false) {
                    $definition['class'] = $this->_completeClassName($name, $definition['class']);
                }
            } elseif (isset($definition[0]) && count($definition) !== 1) {
                if (strpos($definition[0], '\\') === false) {
                    $definition[0] = $this->_completeClassName($name, $definition[0]);
                }
            } else {
                $definition['class'] = $this->_interClassName($name);
            }
        } elseif (is_object($definition)) {
            $definition = ['class' => $definition];
        } else {
            throw new UnexpectedValueException(['`:definition` definition is unknown', 'definition' => $name]);
        }

        $this->_definitions[$name] = $definition;

        return $this;
    }

    /**
     * @param string       $pattern
     * @param string|array $namespaces
     *
     * @return static
     */
    public function setPattern($pattern, $namespaces)
    {
        $this->_patterns[$pattern] = $namespaces;

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
        unset($this->_definitions[$name], $this->_instances[$name], $this->{$name});

        return $this;
    }

    /**
     * @param mixed  $definition
     * @param array  $parameters
     * @param string $name
     *
     * @return mixed
     */
    public function getInstance($definition, $parameters = null, $name = null)
    {
        if (is_string($definition)) {
            $params = [];
        } elseif (isset($definition['class'])) {
            $params = $definition;
            $definition = $definition['class'];
            unset($params['class'], $params['shared']);
        } elseif (isset($definition[0])) {
            $params = $definition;
            $definition = $definition[0];
            unset($params[0], $params['shared']);
        } else {
            $params = [];
        }

        if ($parameters === null) {
            if (!$params || isset($params[0])) {
                $parameters = $params;
            } else {
                $parameters = [$params];
            }
        } elseif (count($parameters) !== 0 && !isset($parameters[0])) {
            $parameters = [$parameters];
        }

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                $definition = $this->alias->resolveNS($definition);
            }

            if (!class_exists($definition)) {
                throw new InvalidValueException(['`:name` component cannot be resolved: `:class` class is not exists',
                    'name' => $name,
                    'class' => $definition]);
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
            throw new NotSupportedException(['`:name` component cannot be resolved: component implement type is not supported', 'name' => $name]);
        }

        if ($instance instanceof Component || method_exists($instance, 'setDi')) {
            $instance->setDi($this);
        }

        return $instance;
    }

    /**
     * Resolves the component based on its configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function get($name, $parameters = null)
    {
        if (isset($this->_instances[$name])) {
            return $this->_instances[$name];
        }

        if (isset($this->_definitions[$name])) {
            $definition = $this->_definitions[$name];
        } else {
            return $this->getInstance($name, $parameters, $name);
        }

        $instance = $this->getInstance($definition, $parameters, $name);

        if (is_string($definition) || !isset($definition['shared']) || $definition['shared'] === true) {
            $this->_instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    protected function _getPatterned($name)
    {
        foreach ($this->_patterns as $pattern => $namespaces) {
            if (fnmatch($pattern, $name)) {
                if (is_string($namespaces)) {
                    return strpos($namespaces, '@') === false ? $namespaces . ucfirst($name) : $this->alias->resolveNS($namespaces . ucfirst($name));
                } else {
                    foreach ($namespaces as $namespace) {
                        $className = strpos($namespace, '@') === false ? $namespace . ucfirst($name) : $this->alias->resolveNS($namespace . ucfirst($name));
                        if (class_exists($className)) {
                            return $className;
                        }
                    }
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Resolves a component, the resolved component is stored in the DI, subsequent requests for this component will return the same instance
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        if (isset($this->_instances[$name])) {
            return $this->_instances[$name];
        }

        if (isset($this->_definitions[$name])) {
            return $this->_instances[$name] = $this->getInstance($this->_definitions[$name], null, $name);
        } elseif (strpos($name, '\\') !== false) {
            return $this->_instances[$name] = $this->getInstance($name, null, $name);
        } else {
            $className = $this->_getPatterned($name);
            if ($className === null) {
                throw new InvalidValueException(['`:component` component is not exists', 'component' => $name]);
            }
            return $this->_instances[$name] = $this->getInstance($className, null, $name);
        }
    }

    /**
     * @param string $name
     *
     * @return string|array|callable|null
     */
    public function getDefinition($name)
    {
        return isset($this->_definitions[$name]) ? $this->_definitions[$name] : null;
    }

    /**
     * Magic method __get
     *
     * @param string $propertyName
     *
     * @return mixed
     */
    public function __get($propertyName)
    {
        return $this->getShared($propertyName);
    }

    /**
     * @param string $name
     * @param mixed  $value
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
        return isset($this->_definitions[$name]);
    }

    /**
     * Magic method to get or set components using setters/getters
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return void
     */
    public function __call($method, $arguments = [])
    {
        throw new BadMethodCallException(['Call to undefined method `:method`', 'method' => $method]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return get_object_vars($this);
    }
}
