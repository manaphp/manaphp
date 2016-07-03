<?php

namespace ManaPHP\Mvc\View\Renderer {

    /**
     * ManaPHP\Mvc\View\EngineInterface initializer
     */
    interface EngineInterface
    {
        /**
         * Renders a view using the template engine
         *
         * @param string $file
         * @param array  $vars
         */
        public function render($file, $vars = null);
    }
}
