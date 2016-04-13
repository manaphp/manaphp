<?php
namespace ManaPHP\Mvc\View {

    use ManaPHP\Component;
    use ManaPHP\Mvc\View\Renderer\EngineInterface;
    use ManaPHP\Mvc\View\Renderer\Exception;

    class Renderer extends Component implements RendererInterface
    {
        /**
         * @var \ManaPHP\Mvc\View\Renderer\EngineInterface[]
         */
        protected $_resolvedEngines = [];

        /**
         * @var array
         */
        protected $_registeredEngines = [];

        public function __construct()
        {
            $this->_registeredEngines['.phtml'] = 'ManaPHP\Mvc\View\Renderer\Engine\Php';
            $this->_registeredEngines['.html'] = 'ManPHP\Mvc\View\Renderer\Engine\Html';
        }

        /**
         * @param string $extension
         *
         * @return \ManaPHP\Mvc\View\Renderer\EngineInterface
         * @throws \ManaPHP\Mvc\View\Renderer\Exception
         */
        protected function _loadEngine($extension)
        {
            $arguments = [$this->_dependencyInjector];
            $engine = $this->_registeredEngines[$extension];
            if ($engine instanceof \Closure) {
                $engine = call_user_func_array($engine, $arguments);
            } elseif (is_string($engine)) {
                $engine = $this->_dependencyInjector->getShared($engine, $arguments);
            }

            if (!$engine instanceof EngineInterface) {
                throw new Exception('Invalid template engine: it is not implements \ManaPHP\Mvc\Renderer\EngineInterface');
            }

            return $engine;
        }

        /** @noinspection PhpDocMissingThrowsInspection */
        /**
         * Checks whether $template exists on registered extensions and render it
         *
         * @noinspection PhpDocMissingThrowsInspection
         *
         * @param string  $template
         * @param boolean $directOutput
         * @param array   $vars
         *
         * @return static
         * @throws \ManaPHP\Mvc\View\Renderer\Exception
         */
        public function render($template, $vars, $directOutput = true)
        {
            $notExists = true;
            $content = null;

            foreach ($this->_registeredEngines as $extension => $engine) {
                $file = $template . $extension;
                if (file_exists($file)) {
                    if (DIRECTORY_SEPARATOR === '\\') {
                        $realPath = str_replace('\\', '/', realpath($file));
                        if ($file !== $realPath) {
                            trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                        }
                    }

                    if (!isset($this->_resolvedEngines[$extension])) {
                        $this->_resolvedEngines[$extension] = $this->_loadEngine($extension);
                    }

                    $engine = $this->_resolvedEngines[$extension];

                    $eventArguments = ['file' => $file, 'vars' => $vars];
                    $this->fireEvent('renderer:beforeRenderView', $eventArguments);

                    if ($directOutput) {
                        $engine->render($file, $vars);
                        $content = null;
                    } else {
                        ob_start();

                        try {
                            $engine->render($file, $vars);
                        } catch (\Exception $e) {
                            ob_end_clean();

                            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                            throw $e;
                        }

                        $content = ob_get_clean();
                    }

                    $notExists = false;
                    $this->fireEvent('renderer:afterRenderView', $eventArguments);
                    break;
                }
            }

            if ($notExists) {
                throw new Exception("View '$template' was not found in the views directory");
            }

            return $content;
        }

        /**
         * @param string $template
         *
         * @return bool
         */
        public function exists($template)
        {
            foreach ($this->_registeredEngines as $extension => $engine) {
                if (is_file($template . $extension)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Register template engines
         *
         *<code>
         *$renderer->registerEngines(array(
         *  ".phtml" => "ManaPHP\Mvc\View\Renderer\Engine\Php",
         *  ".html" => "ManaPHP\Mvc\View\Renderer\Engine\Html",
         *));
         *</code>
         *
         * @param array $engines
         *
         * @return static
         */
        public function registerEngines($engines)
        {
            $this->_registeredEngines = $engines;

            return $this;
        }

        /**
         * Returns the registered template engines
         *
         * @brief array \ManaPHP\Mvc\View::getRegisteredEngines()
         */
        public function getRegisteredEngines()
        {
            return $this->_registeredEngines;
        }
    }
}