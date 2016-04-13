<?php

namespace ManaPHP {

    use ManaPHP\Di\Exception;
    use ManaPHP\Di\Service;

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
         * @var \ManaPHP\Di\ServiceInterface[]
         */
        protected $_services = [];

        /**
         * @var array
         */
        protected $_sharedInstances = [];

        /**
         * Latest DI build
         */
        protected static $_default = null;

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
         *
         * @return \ManaPHP\Di\ServiceInterface
         */
        public function set($name, $definition, $shared = false)
        {
            return $this->_services[$name] = new Service($name, $definition, $shared);
        }

        /**
         * Removes a service in the services container
         *
         * @param string $name
         *
         * @return static
         */
        public function remove($name)
        {
            unset($this->_services[$name], $this->_sharedInstances[$name]);

            return $this;
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
            //region DEBUG
            assert(is_string($name), 'service name is not a string:' . json_encode($name, JSON_UNESCAPED_SLASHES));
            //endregion

            if (isset($this->_services[$name])) {
                $instance = $this->_services[$name]->resolve($parameters, $this);
            } else {
                if (!class_exists($name)) {
                    throw new Exception("Service '$name' wasn't found in the dependency injection container");
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
            return isset($this->_services[$name]);
        }

        /**
         * Registers an "always shared" service in the services container
         *
         * @param string $name
         * @param mixed  $definition
         *
         * @return \ManaPHP\Di\ServiceInterface
         */
        public function setShared($name, $definition)
        {
            return $this->_services[$name] = new Service($name, $definition, true);
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
