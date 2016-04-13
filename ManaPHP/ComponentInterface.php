<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/18
 */

namespace ManaPHP {

    interface ComponentInterface
    {

        /**
         * Magic method __get
         *
         * @param string $propertyName
         *
         * @return object
         */
        public function __get($propertyName);

        /**
         * Sets the dependency injector
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @return static
         */
        public function setDependencyInjector($dependencyInjector);

        /**
         * Returns the internal dependency injector
         *
         * @return \ManaPHP\DiInterface
         */
        public function getDependencyInjector();

        /**
         * Attach a listener to the events manager
         *
         * @param string                                    $event
         * @param callable|\ManaPHP\Event\ListenerInterface $handler
         *
         * @return static
         * @throws \ManaPHP\Event\Exception
         */
        public function attachEvent($event, $handler);

        /**
         * Fires an event in the events manager causing that the active listeners will be notified about it
         *
         * @param string $event
         * @param mixed  $data
         *
         * @return mixed
         */
        public function fireEvent($event, $data = null);
    }
}