<?php
namespace ManaPHP;

use ManaPHP\Renderer\Exception as RendererException;

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
     * Renderer constructor.
     *
     * @param array $engines
     */
    public function __construct(
        $engines = [
            '.sword' => 'ManaPHP\Renderer\Engine\Sword',
            '.phtml' => 'ManaPHP\Renderer\Engine\Php',
        ]
    ) {
        $this->_engines = $engines;
    }

    /**
     * Checks whether $template exists on registered extensions and render it
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     * @throws \ManaPHP\Renderer\Exception
     * @throws \Exception
     */
    public function render($template, $vars = [], $directOutput = false)
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
                    $this->_resolved[$extension] = $this->_dependencyInjector->getShared($engine, [$this->_dependencyInjector]);
                }

                $engine = $this->_resolved[$extension];

                $eventArguments = ['file' => $file, 'vars' => $vars];
                $this->fireEvent('renderer:beforeRender', $eventArguments);

                if (isset($vars['renderer'])) {
                    throw new RendererException('variable `renderer` is reserved for renderer'/**m04c9833791ad0d92b*/);
                }
                $vars['renderer'] = $this;

                if (isset($vars['di'])) {
                    throw new RendererException('variable `di` is reserved for renderer'/**m0351d1318ca365b9b*/);
                }
                $vars['di'] = $this->_dependencyInjector;

                if (isset($vars['url'])) {
                    throw new RendererException('variable `url` is reserved for renderer'/**m0394e70537f32f733*/);
                }
                $vars['url'] = isset($this->url) ? $this->url : null;

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
            throw new RendererException('`:template` with `:extensions` extension file was not found'/**m0312a7f5d4bc76939*/,
                ['template' => $template, 'extensions' => implode(', or ', array_keys($this->_engines))]);
        }

        return $content;
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     * @throws \Exception
     */
    public function partial($path, $vars = [])
    {
        $this->render($path, $vars, true);
    }

    /**
     * @param string $template
     *
     * @return bool
     * @throws \ManaPHP\Renderer\Exception
     */
    public function exists($template)
    {
        foreach ($this->_engines as $extension => $_) {
            $file = $template . $extension;
            if (is_file($file)) {
                if (PHP_EOL !== "\n") {
                    $realPath = str_replace('\\', '/', realpath($file));
                    if ($file !== $realPath) {
                        throw new RendererException('`:real_file` file name does case mismatch for `:wanted_file`', ['real_file' => $realPath, 'wanted_file' => $file]);
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
     * @throws \ManaPHP\Renderer\Exception
     */
    public function stopSection($overwrite = false)
    {
        if (count($this->_sectionStack) === 0) {
            throw new RendererException('cannot stop a section without first starting session'/**m0005e5105f6b924c8*/);
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
            throw new RendererException('Cannot append a section without first starting one:'/**m0612bf4d28a6f9d36*/);
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