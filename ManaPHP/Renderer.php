<?php
namespace ManaPHP;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;
use ManaPHP\Renderer\Exception;

class Renderer extends Component implements RendererInterface
{
    /**
     * @var \ManaPHP\Renderer\EngineInterface[]
     */
    protected $_resolved = [];

    /**
     * @var array
     */
    protected $_engines = [];

    /**
     * @var array
     */
    protected $_sections = [];

    /**
     * @var array
     */
    protected $_sectionStack = [];

    public function __construct(
        $engines = [
            '.sword' => 'ManaPHP\Renderer\Engine\Sword',
            '.phtml' => 'ManaPHP\Renderer\Engine\Php',
        ]
    ) {
        $this->_engines = $engines;
    }

    /**
     * @param string $extension
     *
     * @return \ManaPHP\Renderer\EngineInterface
     * @throws \ManaPHP\Renderer\Exception
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
            throw new Exception('Invalid template engine: it is not implements \ManaPHP\Renderer\EngineInterface');
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
     * @throws \ManaPHP\Renderer\Exception
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

                if (isset($vars['renderer'])) {
                    throw new Exception('variable \'renderer\' is reserved for PHP renderer engine.');
                }
                $vars['renderer'] = $this;
                
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

    /**
     * Get the string contents of a section.
     *
     * @param  string $section
     * @param  string $default
     *
     * @return string
     */
    public function getSection($section, $default = '')
    {
        if (isset($this->_sections[$section])) {
            return $this->_sections[$section];
        } else {
            return $default;
        }
    }

    /**
     * Start injecting content into a section.
     *
     * @param  string $section
     *
     * @return void
     */
    public function startSection($section)
    {
        ob_start();
        $this->_sectionStack[] = $section;
    }

    /**
     * Stop injecting content into a section.
     *
     * @param  bool $overwrite
     *
     * @return string
     * @throws \ManaPHP\Renderer\Exception
     */
    public function stopSection($overwrite = false)
    {
        if (count($this->_sectionStack) === 0) {
            throw new Exception('Cannot stop a section without first starting one:');
        }

        $last = array_pop($this->_sectionStack);
        if ($overwrite || !isset($this->_sections[$last])) {
            $this->_sections[$last] = ob_get_clean();
        } else {
            $this->_sections[$last] .= ob_get_clean();
        }
    }

    /**
     * @return void
     * @throws \ManaPHP\Renderer\Exception
     */
    public function appendSection()
    {
        if (count($this->_sectionStack) === 0) {
            throw new Exception('Cannot append a section without first starting one:');
        }

        $last = array_pop($this->_sectionStack);
        if (isset($this->_sections[$last])) {
            $this->_sections[$last] .= ob_get_clean();
        } else {
            $this->_sections[$last] = ob_get_clean();
        }
    }

    /**
     * @param string $v
     *
     * @return string
     */
    public function escape($v)
    {
        return htmlentities($v, ENT_QUOTES, 'UTF-8', false);
    }
}