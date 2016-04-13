<?php

namespace ManaPHP\Mvc\View\Renderer {

    /**
     * ManaPHP\Mvc\View\EngineInterface initializer
     */
    interface EngineInterface
    {

        /**
         * Php constructor.
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         */
        public function __construct($dependencyInjector = null);

        /**
         * Renders a view using the template engine
         *
         * @param string $file
         * @param array  $vars
         */
        public function render($file, $vars = null);
    }
}
