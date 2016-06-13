<?php

namespace ManaPHP {

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
         */
        protected static $_default;

        /**
         * \ManaPHP\Di constructor
         *
         * @var self
         */
        public function __construct()
        {
            if (self::$_default === null) {
                self::$_default = $this;
            }
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
                unset($this->_services[$name], $this->_sharedInstances[$name]);
            }

            return $this;
        }

        /**
         * Resolves the service
         *
         * @param string $name
         * @param mixed  $definition
         * @param array  $parameters
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception
         */
        protected function _resolve($name, $definition, $parameters = null)
        {
            $instance = null;

            if (is_string($definition)) {
                if (class_exists($definition)) {
                    if (is_array($parameters)) {
                        $reflection = new \ReflectionClass($definition);
                        $instance = $reflection->newInstanceArgs($parameters);
                    } else {
                        $instance = new $definition();
                    }
                } else {
                    throw new Exception("Service '$name' cannot be resolved: class is not exists.");
                }
            } elseif ($definition instanceof \Closure) {
                if (is_array($parameters)) {
                    $instance = call_user_func_array($definition, $parameters);
                } else {
                    $instance = call_user_func($definition);
                }
            } elseif (is_object($definition)) {
                $instance = $definition;
            } else {
                throw new Exception("Service '$name' cannot be resolved: service type is unknown.");
            }

            return $instance;
        }

        /**
         * Resolves the service based on its configuration
         *
         * @param string $name
         * @param array  $parameters
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception
         */
        public function get($name, $parameters = null)
        {
            assert(is_string($name), 'service name is not a string:' . json_encode($name, JSON_UNESCAPED_SLASHES));

            if (isset($this->_aliases[$name])) {
                $name = $this->_aliases[$name];
            }

            if (isset($this->_services[$name])) {
                if (is_string($this->_services[$name])) {
                    $definition = $this->_services[$name];
                    $shared = true;
                } else {
                    list($definition, $shared) = $this->_services[$name];
                }

                if ($shared && isset($this->_sharedInstances[$name])) {
                    $instance = $this->_sharedInstances[$name];
                } else {
                    $instance = $this->_resolve($name, $definition, $parameters);

                    if ($shared) {
                        $this->_sharedInstances[$name] = $instance;
                    }
                }
            } else {
                if (!class_exists($name)) {
                    throw new Exception("Service '$name' cannot be resolved: class is not exists.");
                }

                if (is_array($parameters)) {
                    $reflection = new \ReflectionClass($name);
                    $instance = $reflection->newInstanceArgs($parameters);
                } else {
                    $instance = new $name();
                }
            }

            if ($instance instanceof ComponentInterface) {
                $instance->setDependencyInjector($this);
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
         * @throws \ManaPHP\Di\Exception
         */
        public function getShared($name, $parameters = null)
        {
            if (isset($this->_aliases[$name])) {
                $name = $this->_aliases[$name];
            }

            if (!isset($this->_sharedInstances[$name])) {
                $this->_sharedInstances[$name] = $this->get($name, $parameters);
            }

            return $this->_sharedInstances[$name];
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
        public function __call($method, $arguments = null)
        {
            throw new Exception("Call to undefined method or service '$method'");
        }

        /**
         * @return array
         */
        public function __debugInfo()
        {
            return get_object_vars($this) ?: [];
        }
    }
}
