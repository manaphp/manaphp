<?php

namespace ManaPHP\Mvc {

    /**
     * ManaPHP\Mvc\ModuleInterface initializer
     */
    interface ModuleInterface
    {
        /**
         * Registers services related to the module
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         */
        public function registerServices($dependencyInjector);

        /**
         * @param string $controller
         * @param string $action
         *
         * @return false|void
         */
        public function authorize($controller, $action);
    }
}
