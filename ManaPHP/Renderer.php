<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
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
    protected $_engines = [
        '.sword' => 'ManaPHP\Renderer\Engine\Sword',
        '.phtml' => 'ManaPHP\Renderer\Engine\Php'];

    /**
     * @var array
     */
    protected $_sections = [];

    /**
     * @var array
     */
    protected $_sectionStack = [];

    /**
     * @var array
     */
    protected $_templates = [];

    /**
     * Renderer constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['engines'])) {
            $this->_engines = $options['engines'] ?: ['.phtml' => 'ManaPHP\Renderer\Engine\Php'];
        }
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
        $content = null;

        if (DIRECTORY_SEPARATOR === '\\' && strpos($template, '\\') !== false) {
            $template = str_replace('\\', '/', $template);
        }

        if ($template[0] === '@') {
            $template = $this->alias->resolve($template);
        } elseif ($template[0] === '/' || (isset($template[1]) && $template[1] === ':')) {
            null;
        } elseif (strpos($template, '/') !== false) {
            throw new MisuseException(['`:template` template can not contains relative path', 'template' => $template]);
        } else {
            $template = dirname(end($this->_templates)) . '/' . $template;
        }

        $notExists = true;
        $extension = null;
        $file = null;
        foreach ($this->_engines as $extension => $engine) {
            if (is_file($file = $template . $extension)) {
                $notExists = false;
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

        if (PHP_EOL !== "\n") {
            $realPath = strtr(realpath($file), '\\', '/');
            if ($file !== $realPath) {
                trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
            }
        }

        if (!isset($this->_resolved[$extension])) {
            $engine = $this->_resolved[$extension] = $this->_di->getShared($this->_engines[$extension]);
        } else {
            $engine = $this->_resolved[$extension];
        }

        if (isset($vars['renderer'])) {
            throw new MisuseException('variable `renderer` is reserved for renderer');
        }
        $vars['renderer'] = $this;

        if (isset($vars['di'])) {
            throw new MisuseException('variable `di` is reserved for renderer');
        }
        $vars['di'] = $this->_di;

        $this->_templates[] = $template;

        $eventArguments = ['file' => $file, 'vars' => $vars];
        $this->fireEvent('renderer:beforeRender', $eventArguments);

        if ($directOutput) {
            $engine->render($file, $vars);
            $content = null;
        } else {
            ob_start();
            ob_implicit_flush(false);
            $engine->render($file, $vars);
            $content = ob_get_clean();
        }

        $this->fireEvent('renderer:afterRender', $eventArguments);

        array_pop($this->_templates);

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
        if (DIRECTORY_SEPARATOR === '\\' && strpos($template, '\\') !== false) {
            $template = str_replace('\\', '/', $template);
        }

        if ($template[0] === '@') {
            $template = $this->alias->resolve($template);
        } elseif ($template[0] === '/' || (isset($template[1]) && $template[1] === ':')) {
            null;
        } elseif (strpos($template, '/') !== false) {
            throw new MisuseException(['`:template` template can not contains relative path', 'template' => $template]);
        } else {
            $template = dirname(end($this->_templates)) . '/' . $template;
        }

        foreach ($this->_engines as $extension => $_) {
            if (is_file($file = $template . $extension)) {
                if (PHP_EOL !== "\n") {
                    $realPath = strtr(realpath($file), '\\', '/');
                    if ($file !== $realPath) {
                        throw new PathCaseSensitiveException(['`:real_file` file name does case mismatch for `:wanted_file`',
                            'real_file' => $realPath,
                            'wanted_file' => $file]);
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