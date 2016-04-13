<?php

namespace ManaPHP\Di {

    /**
     * ManaPHP\Di\Service
     *
     * Represents individually a service in the services container
     *
     *<code>
     * $service = new ManaPHP\Di\Service('request', 'ManaPHP\Http\Request');
     * $request = $service->resolve();
     *</code>
     *
     */
    class Service implements ServiceInterface
    {

        /**
         * @var string
         */
        protected $_name;

        /**
         * @var callable|string|object
         */
        protected $_definition;

        /**
         * @var boolean
         */
        protected $_shared;

        /**
         * @var boolean
         */
        protected $_resolved = false;

        /**
         * @var object
         */
        protected $_sharedInstance;

        /**
         * \ManaPHP\Di\Service
         *
         * @param string                 $name
         * @param string|callable|object $definition
         * @param boolean                $shared
         */
        public function __construct($name, $definition, $shared)
        {
            $this->_name = $name;
            $this->_definition = $definition;
            $this->_shared = $shared;
        }

        /**
         * Resolves the service
         *
         * @param array                $parameters
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @return object
         * @throws \ManaPHP\Exception
         */
        public function resolve($parameters = null, $dependencyInjector = null)
        {
            if ($this->_shared && $this->_sharedInstance !== null) {
                return $this->_sharedInstance;
            }

            $instance = null;
            $definition = $this->_definition;
            $canNotBeResolvedError = "Service '{$this->_name}' cannot be resolved";

            if (is_string($definition)) {
                if (class_exists($definition)) {
                    if (is_array($parameters)) {
                        $reflection = new \ReflectionClass($definition);
                        $instance = $reflection->newInstanceArgs($parameters);
                    } else {
                        $instance = new $definition();
                    }
                } else {
                    throw new Exception($canNotBeResolvedError);
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
                throw new Exception($canNotBeResolvedError);
            }

            if ($this->_shared) {
                $this->_sharedInstance = $instance;
            }

            $this->_resolved = true;

            return $instance;
        }

        /**
         * Returns true if the service was resolved
         *
         * @return bool
         */
        public function isResolved()
        {
            return $this->_resolved;
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
