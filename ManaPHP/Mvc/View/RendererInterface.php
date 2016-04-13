<?php
namespace ManaPHP\Mvc\View {

    interface RendererInterface
    {

        /**
         * Register template engines
         *
         * @param array $engines
         *
         * @return static
         */
        public function registerEngines($engines);

        /**
         * Returns the registered template engines
         *
         * @brief array \ManaPHP\Mvc\View::getRegisteredEngines()
         */
        public function getRegisteredEngines();

        /**
         * Checks whether view exists on registered extensions and render it
         *
         * @param string  $template
         * @param boolean $directOutput
         * @param array   $vars
         *
         * @return static
         * @throws \ManaPHP\Mvc\View\Exception
         */
        public function render($template, $vars, $directOutput = true);

        /**
         * @param string $template
         *
         * @return bool
         */
        public function exists($template);
    }
}