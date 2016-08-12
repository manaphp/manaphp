<?php
namespace ManaPHP\Mvc\View;

use ManaPHP\Component;
use ManaPHP\Mvc\View\Renderer\EngineInterface;
use ManaPHP\Mvc\View\Renderer\Exception;

class Renderer extends Component implements RendererInterface
{
    /**
     * @var \ManaPHP\Mvc\View\Renderer\EngineInterface[]
     */
    protected $_resolved = [];

    /**
     * @var array
     */
    protected $_engines = [];

    public function __construct(
        $engines = [
            '.sword' => 'ManaPHP\Mvc\View\Renderer\Engine\Sword',
            '.phtml' => 'ManaPHP\Mvc\View\Renderer\Engine\Php',
        ]
    ) {
        $this->_engines = $engines;
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
        $engine = $this->_engines[$extension];
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
     * @return string
     * @throws \ManaPHP\Mvc\View\Renderer\Exception
     */
    public function render($template, $vars, $directOutput = true)
    {
        $notExists = true;
        $content = null;

        foreach ($this->_engines as $extension => $engine) {
            $file = $this->alias->resolve($template . $extension);
            if (file_exists($file)) {
                if (PHP_EOL !== "\n") {
                    $realPath = str_replace('\\', '/', realpath($file));
                    if ($file !== $realPath) {
                        trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                    }
                }

                if (!isset($this->_resolved[$extension])) {
                    $this->_resolved[$extension] = $this->_loadEngine($extension);
                }

                $engine = $this->_resolved[$extension];

                $eventArguments = ['file' => $file, 'vars' => $vars];
                $this->fireEvent('renderer:beforeRender', $eventArguments);

                if (isset($vars['view'])) {
                    throw new Exception('variable \'view\' is reserved for PHP renderer engine.');
                }
                $vars['view'] = $this->_dependencyInjector->has('view') ? $this->_dependencyInjector->getShared('view') : null;

                if (isset($vars['di'])) {
                    throw new Exception('variable \'di\' is reserved for PHP renderer engine.');
                }
                $vars['di'] = $this->_dependencyInjector;

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
                $this->fireEvent('renderer:afterRender', $eventArguments);
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
        foreach ($this->_engines as $extension => $_) {
            $file = $template . $extension;
            if (is_file($file)) {
                if (PHP_EOL !== "\n") {
                    $realPath = str_replace('\\', '/', realpath($file));
                    if ($file !== $realPath) {
                        trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                    }
                }
                return true;
            }
        }

        return false;
    }
}