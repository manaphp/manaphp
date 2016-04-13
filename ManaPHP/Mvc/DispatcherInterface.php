<?php

namespace ManaPHP\Mvc {

    /**
     * ManaPHP\Mvc\DispatcherInterface initializer
     */
    interface DispatcherInterface
    {
        /**
         * Sets the root namespace
         *
         * @param string $namespace
         *
         * @return static
         */
        public function setRootNamespace($namespace);

        /**
         * Returns the root namespace
         *
         * @return string
         */
        public function getRootNamespace();

        /**
         * Gets the module where the controller class is
         */
        public function getModuleName();

        /**
         * Gets last dispatched action name
         *
         * @return string
         */
        public function getActionName();

        /**
         * Gets action params
         *
         * @return array
         */
        public function getParams();

        /**
         * Gets a param by its name or numeric index
         *
         * @param  string|int   $param
         * @param  string|array $filters
         * @param mixed         $defaultValue
         *
         * @return mixed
         */
        public function getParam($param, $filters = null, $defaultValue = null);

        /**
         * Returns value returned by the latest dispatched action
         *
         * @return mixed
         */
        public function getReturnedValue();

        /**
         * Dispatches a handle action taking into account the routing parameters
         *
         * @param string $module
         * @param string $controller
         * @param string $action
         * @param array  $params
         *
         * @return false|\ManaPHP\Mvc\ControllerInterface
         */
        public function dispatch($module, $controller, $action, $params = null);

        /**
         * Forwards the execution flow to another controller/action
         *
         * @param string|array $forward
         */
        public function forward($forward);

        /**
         * Check if the current executed action was forwarded by another one
         *
         * @return boolean
         */
        public function wasForwarded();

        /**
         * Gets last dispatched controller name
         *
         * @return string
         */
        public function getControllerName();

        /**
         * Returns the previous controller in the dispatcher
         *
         * @return string
         */
        public function getPreviousControllerName();

        /**
         * Returns the previous action in the dispatcher
         *
         * @return string
         */
        public function getPreviousActionName();
    }
}
