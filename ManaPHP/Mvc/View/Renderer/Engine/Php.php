<?php

namespace ManaPHP\Mvc\View\Renderer\Engine {

    use ManaPHP\Component;
    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    /**
     * ManaPHP\Mvc\View\Engine\Php
     *
     * Adapter to use PHP itself as template engine
     */
    class Php extends Component implements EngineInterface
    {
        /**
         * Php constructor.
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         */
        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector;
        }

        /**
         * Renders a view using the template engine
         *
         * @param string $file
         * @param array  $vars
         *
         * @throws \ManaPHP\Mvc\View\Renderer\Engine\Exception
         */
        public function render($file, $vars = null)
        {
            if (isset($vars['view'])) {
                throw new Exception('variable \'view\' is reserved for PHP view engine.');
            }
            $view = $this->_dependencyInjector->has('view') ? $this->_dependencyInjector->get('view') : null;
            true || $view;

            if (isset($vars['renderer'])) {
                throw new Exception('variable \'render\' is reserved for PHP view engine.');
            }
            $renderer = $this;

            true || $renderer;

            if (is_array($vars)) {
                extract($vars, EXTR_SKIP);
            }

            /** @noinspection PhpIncludeInspection */
            require($file);
        }
    }
}
