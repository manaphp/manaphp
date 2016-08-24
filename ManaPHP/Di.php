<?php

namespace ManaPHP;

use ManaPHP\Di\Exception;

/**
 * ManaPHP\Di
 *
 * ManaPHP\Di is a component that implements Dependency Injection/Service Location
 * of services and it's itself a container for them.
 *
 * Since ManaPHP is highly decoupled, ManaPHP\Di is essential to integrate the different
 * components of the framework. The developer can also use this component to inject dependencies
 * and manage global instances of the different classes used in the application.
 *
 * Basically, this component implements the `Inversion of Control` pattern. Applying this,
 * the objects do not receive their dependencies using setters or constructors, but requesting
 * a service dependency injector. This reduces the overall complexity, since there is only one
 * way to get the required dependencies within a component.
 *
 * Additionally, this pattern increases testability in the code, thus making it less prone to errors.
 *
 *<code>
 * $di = new ManaPHP\Di();
 *
 * //Using a string definition
 * $di->set('request', 'ManaPHP\Http\Request', true);
 *
 * //Using an anonymous function
 * $di->set('request', function(){
 *      return new ManaPHP\Http\Request();
 * }, true);
 *
 * $request = $di->getRequest();
 *
 *</code>
 *
 * * @property \ManaPHP\Alias $alias
 * @property \ManaPHP\Mvc\Dispatcher              $dispatcher
 * @property \ManaPHP\Mvc\Router                  $router
 * @property \ManaPHP\Mvc\Url                     $url
 * @property \ManaPHP\Http\Request                $request
 * @property \ManaPHP\Http\Filter                 $filter
 * @property \ManaPHP\Http\Response               $response
 * @property \ManaPHP\Http\Cookies                $cookies
 * @property \ManaPHP\Mvc\View\Flash              $flash
 * @property \ManaPHP\Mvc\View\Flash              $flashSession
 * @property \ManaPHP\Http\SessionInterface       $session
 * @property \ManaPHP\Event\ManagerInterface      $eventsManager
 * @property \ManaPHP\Db                          $db
//* @property \ManaPHP\Security $security
 * @property \ManaPHP\Security\Crypt              $crypt
 * @property \ManaPHP\Mvc\Model\Manager           $modelsManager
 * @property \ManaPHP\Mvc\Model\Metadata          $modelsMetadata
//     * @property \ManaPHP\Assets\Manager $assets
 * @property \ManaPHP\Di|\ManaPHP\DiInterface     $di
 * @property \ManaPHP\Http\Session\Bag            $persistent
 * @property \ManaPHP\Mvc\View                    $view
 * @property \ManaPHP\Mvc\View\Tag                $tag
 * @property \ManaPHP\Loader                      $loader
 * @property \ManaPHP\Log\Logger                  $logger
 * @property \ManaPHP\Renderer                    $renderer
 * @property \Application\Configure               $configure
 * @property \ManaPHP\ApplicationInterface        $application
 * @property \ManaPHP\Debugger                    $debugger
 * @property \ManaPHP\Authentication\Password     $password
 * @property \Redis                               $redis
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 * @property \ManaPHP\Cache                       $cache
 * @property \ManaPHP\Counter                     $counter
 * @property \ManaPHP\CacheInterface              $viewsCache
 * @property \ManaPHP\Http\Client                 $httpClient
 * @property \ManaPHP\AuthorizationInterface      $authorization
 * @property \ManaPHP\Security\Captcha            $captcha
 * @property \ManaPHP\Security\CsrfToken          $csrfToken
 * @property \ManaPHP\Authentication\UserIdentity $userIdentity
 * @property \ManaPHP\Paginator                   $paginator
 */
class Di implements DiInterface
{

    /**
     * @var array
     */
    protected $_services = [];

    /**
     * @var array
     */
    protected $_aliases = [];

    /**
     * @var array
     */
    protected $_sharedInstances = [];

    /**
     * Latest DI build
     *
     * @var \ManaPHP\Di
     */
    protected static $_default;

    public function __construct()
    {
        self::$_default = $this;
    }

    /**
     * Return the latest DI created
     *
     * @return \ManaPHP\Di
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Registers a service in the services container
     *
     * @param string  $name
     * @param mixed   $definition
     * @param boolean $shared
     * @param array   $aliases
     *
     * @return void
     */
    public function set($name, $definition, $shared = false, $aliases = [])
    {
        foreach ($aliases as $alias) {
            $this->_aliases[$alias] = $name;
        }

        if ($shared && is_string($definition)) {
            $this->_services[$name] = $definition;
        } else {
            $this->_services[$name] = [$definition, $shared];
        }
    }

    /**
     * Removes a service in the services container
     *
     * @param string $name
     *
     * @return static
     * @throws \ManaPHP\Di\Exception
     */
    public function remove($name)
    {
        if (in_array($name, $this->_aliases, true)) {
            throw new Exception("Service($name) is being used by alias, please remove alias first.");
        }

        if (isset($this->_aliases[$name])) {
            unset($this->_aliases[$name]);
        } else {
            unset($this->_services[$name], $this->_sharedInstances[$name], $this->{$name});
        }

        return $this;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Resolves the service based on its configuration
     *
     * @param string $_name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function get($_name, $parameters = [])
    {
        $name = (!isset($this->_services[$_name]) && isset($this->_aliases[$_name])) ? $this->_aliases[$_name] : $_name;

        if (isset($this->_services[$name])) {
            if (is_string($this->_services[$name])) {
                $definition = $this->_services[$name];
                $shared = true;
            } else {
                $parts = $this->_services[$name];
                $definition = $parts[0];
                $shared = $parts[1];
            }
        } else {
            $definition = $name;
            $shared = false;
        }

        if (isset($this->_sharedInstances[$name])) {
            return $this->_sharedInstances[$name];
        }

        if (isset($this->eventsManager)) {
            $this->eventsManager->fireEvent('di:beforeResolve', $this, ['name' => $_name, 'parameters' => $parameters]);
        }

        if (is_string($definition)) {
            if (!class_exists($definition)) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new Exception("Service '$_name' cannot be resolved: class is not exists.");
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
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new Exception("Service '$_name' cannot be resolved: service type is unknown.");
        }

        if ($shared) {
            $this->_sharedInstances[$name] = $instance;
        }

        if ($instance instanceof Component) {
            $instance->setDependencyInjector($this);
        }

        if (isset($this->eventsManager)) {
            $this->eventsManager->fireEvent('di:afterResolve', $this, ['name' => $_name, 'parameters' => $parameters, 'instance' => $instance]);
        }

        return $instance;
    }

    /**
     * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getShared($name, $parameters = [])
    {
        if (!isset($this->_sharedInstances[$name])) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_sharedInstances[$name] = $this->get($name, $parameters);
        }

        return $this->_sharedInstances[$name];
    }

    /** @noinspection MagicMethodsValidityInspection */
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
        if ($this->has($propertyName)) {
            $this->{$propertyName} = $this->getShared($propertyName);
            return $this->{$propertyName};
        }

        return null;
    }

    /**
     * Check whether the DI contains a service by a name
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->_services[$name]) || isset($this->_aliases[$name]);
    }

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string $name
     * @param mixed  $definition
     * @param array  $aliases
     *
     * @return void
     */
    public function setShared($name, $definition, $aliases = [])
    {
        $this->set($name, $definition, true, $aliases);
    }

    /**
     * Magic method to get or set services using setters/getters
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return void
     * @throws \ManaPHP\Di\Exception
     */
    public function __call($method, $arguments = [])
    {
        throw new Exception("Call to undefined method or service '$method'");
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return get_object_vars($this);
    }
}
