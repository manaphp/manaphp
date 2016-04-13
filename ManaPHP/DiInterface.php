<?php

namespace ManaPHP {

    /**
     * ManaPHP\DiInterface initializer
     */
    interface DiInterface
    {

        /**
         * Registers a service in the service container
         *
         * @param string  $name
         * @param mixed   $definition
         * @param boolean $shared
         *
         * @return \ManaPHP\Di\ServiceInterface
         */
        public function set($name, $definition, $shared = false);

        /**
         * Registers an "always shared" service in the services container
         *
         * @param string $name
         * @param mixed  $definition
         *
         * @return \ManaPHP\Di\ServiceInterface
         */
        public function setShared($name, $definition);

        /**
         * Removes a service from the service container
         *
         * @param string $name
         *
         * @return static
         */
        public function remove($name);

        /**
         * Resolves the service based on its configuration
         *
         * @param string $name
         * @param array  $parameters
         *
         * @return mixed
         */
        public function get($name, $parameters = null);

        /**
         * Resolves a shared service based on their configuration
         *
         * @param string $name
         * @param array  $parameters
         *
         * @return mixed
         */
        public function getShared($name, $parameters = null);

        /**
         * Check whether the DI contains a service by a name
         *
         * @param string $name
         *
         * @return boolean
         */
        public function has($name);

        /**
         * Return the last DI created
         *
         * @return static
         */
        public static function getDefault();
    }
}
