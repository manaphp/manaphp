<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PathCaseSensitiveException;
use ManaPHP\Exception\PreconditionException;

/**
 * Class ManaPHP\Renderer
 *
 * @package renderer
 */
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

    /**
     * @var string
     */
    protected $_current_template;

    /**
     * Renderer constructor.
     *
     * @param array $engines
     */
    public function __construct(
        $engines = [
            '.sword' => 'ManaPHP\Renderer\Engine\Sword',
            '.phtml' => 'ManaPHP\Renderer\Engine\Php',
            '.html' => 'ManaPHP\Renderer\Engine\Html',
        ]
    )
    {
        $this->_engines = $engines;
    }

    /**
     * Checks whether $template exists on registered extensions and render it
     *
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     */
    public function render($template, $vars = [], $directOutput = false)
    {
        $notExists = true;
        $content = null;

        if ($template[0] !== '@') {
            if (strpos($template, '/') !== false) {
                throw new InvalidValueException(['`:template` template can not contains relative path', 'template' => $template]);
            }

            $template = dirname($this->_current_template) . '/' . $template;
        }

        $this->_current_template = $template;

        foreach ($this->_engines as $extension => $engine) {
            $file = $this->alias->resolve($template . $extension);
            if (is_file($file)) {
                if (PHP_EOL !== "\n") {
                    $realPath = strtr(realpath($file), '\\', '/');
                    if ($file !== $realPath) {
                        trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                    }
                }

                if (!isset($this->_resolved[$extension])) {
                    $this->_resolved[$extension] = $this->_di->getShared($engine);
                }

                $engine = $this->_resolved[$extension];

                $eventArguments = ['file' => $file, 'vars' => $vars];
                $this->fireEvent('renderer:beforeRender', $eventArguments);

                if (isset($vars['renderer'])) {
                    throw new InvalidArgumentException('variable `renderer` is reserved for renderer');
                }
                $vars['renderer'] = $this;

                if (isset($vars['di'])) {
                    throw new InvalidArgumentException('variable `di` is reserved for renderer');
                }
                $vars['di'] = $this->_di;

                if (isset($vars['url'])) {
                    throw new InvalidArgumentException('variable `url` is reserved for renderer');
                }
                $vars['url'] = isset($this->url) ? $this->url : null;

                if ($directOutput) {
                    $engine->render($file, $vars);
                    $content = null;
                } else {
                    ob_start();
                    ob_implicit_flush(false);
                    $engine->render($file, $vars);
                    $content = ob_get_clean();
                }

                $notExists = false;
                $this->fireEvent('renderer:afterRender', $eventArguments);
                break;
            }
        }

        if ($notExists) {
            throw new FileNotFoundException([
                '`:template` with `:extensions` extension file was not found',
                'template' => $template,
                'extensions' => implode(', or ', array_keys($this->_engines))
            ]);
        }

        return $content;
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function partial($path, $vars = [])
    {
        $this->render($path, $vars, true);
    }

    /**
     * @param string $template
     *
     * @return bool
     */
    public function exists($template)
    {
        foreach ($this->_engines as $extension => $_) {
            $file = $this->alias->resolve($template . $extension);
            if (is_file($file)) {
                if (PHP_EOL !== "\n") {
                    $realPath = strtr(realpath($file), '\\', '/');
                    if ($file !== $realPath) {
                        throw new PathCaseSensitiveException(['`:real_file` file name does case mismatch for `:wanted_file`', 'real_file' => $realPath, 'wanted_file' => $file]);
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
     * @param string  $default
     *
     * @return void
     */
    public function startSection($section, $default = null)
    {
        if ($default === null) {
            ob_start();
            ob_implicit_flush(false);
            $this->_sectionStack[] = $section;
        } else {
            $this->_sections[$section] = $default;
        }
    }

    /**
     * Stop injecting content into a section.
     *
     * @param  bool $overwrite
     *
     * @return void
     */
    public function stopSection($overwrite = false)
    {
        if (!$this->_sectionStack) {
            throw new PreconditionException('cannot stop a section without first starting session');
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
     */
    public function appendSection()
    {
        if (!$this->_sectionStack) {
            throw new PreconditionException('Cannot append a section without first starting one:');
        }

        $last = array_pop($this->_sectionStack);
        if (isset($this->_sections[$last])) {
            $this->_sections[$last] .= ob_get_clean();
        } else {
            $this->_sections[$last] = ob_get_clean();
        }
    }
}