<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\PreconditionException;

class RendererContext
{
    /**
     * @var array
     */
    public $sections = [];

    /**
     * @var array
     */
    public $stack = [];

    /**
     * @var array
     */
    public $templates = [];
}

/**
 * Class ManaPHP\Renderer
 *
 * @package renderer
 * @property \ManaPHP\RendererContext $_context
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
     * Renderer constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_context = new RendererContext();

        if (isset($options['engines'])) {
            $this->_engines = $options['engines'] ?: ['.phtml' => 'ManaPHP\Renderer\Engine\Php'];
        }

        $this->loader->registerFiles('@manaphp/Renderer/helpers.php');
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
        $context = $this->_context;

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
            $template = dirname(end($context->templates)) . '/' . $template;
        }

        if (!$file = $this->exists($template)) {
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

        $extension = substr($file, $file === $template ? strrpos($template, '.') : strlen($template));
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

        $context->templates[] = $template;

        $eventArguments = ['file' => $file, 'vars' => $vars];
        $this->eventsManager->fireEvent('renderer:beforeRender', $this, $eventArguments);

        if ($directOutput) {
            $engine->render($file, $vars);
            $content = null;
        } else {
            ob_start();
            ob_implicit_flush(false);
            $engine->render($file, $vars);
            $content = ob_get_clean();
        }

        $this->eventsManager->fireEvent('renderer:afterRender', $this, $eventArguments);

        array_pop($context->templates);

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
     * @return string|false
     */
    public function exists($template)
    {
        $context = $this->_context;

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
            $template = dirname(end($context->templates)) . '/' . $template;
        }

        if (($extension = pathinfo($template, PATHINFO_EXTENSION)) && isset($this->_engines[".$extension"])) {
            return $template;
        }

        foreach ($this->_engines as $extension => $_) {
            if (is_file($file = $template . $extension)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Get the string contents of a section.
     *
     * @param string $section
     * @param string $default
     *
     * @return string
     */
    public function getSection($section, $default = '')
    {
        $context = $this->_context;

        if (isset($context->sections[$section])) {
            return $context->sections[$section];
        } else {
            return $default;
        }
    }

    /**
     * Start injecting content into a section.
     *
     * @param string $section
     * @param string $default
     *
     * @return void
     */
    public function startSection($section, $default = null)
    {
        $context = $this->_context;

        if ($default === null) {
            ob_start();
            ob_implicit_flush(false);
            $context->stack[] = $section;
        } else {
            $context->sections[$section] = $default;
        }
    }

    /**
     * Stop injecting content into a section.
     *
     * @param bool $overwrite
     *
     * @return void
     */
    public function stopSection($overwrite = false)
    {
        $context = $this->_context;

        if (!$context->stack) {
            throw new PreconditionException('cannot stop a section without first starting session');
        }

        $last = array_pop($context->stack);
        if ($overwrite || !isset($context->sections[$last])) {
            $context->sections[$last] = ob_get_clean();
        } else {
            $context->sections[$last] .= ob_get_clean();
        }
    }

    /**
     * @return void
     */
    public function appendSection()
    {
        $context = $this->_context;

        if (!$context->stack) {
            throw new PreconditionException('Cannot append a section without first starting one:');
        }

        $last = array_pop($context->stack);
        if (isset($context->sections[$last])) {
            $context->sections[$last] .= ob_get_clean();
        } else {
            $context->sections[$last] = ob_get_clean();
        }
    }
}