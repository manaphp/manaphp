<?php

namespace ManaPHP\Html;

use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Coroutine\Mutex;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\PreconditionException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class RendererContext implements Inseparable
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
 * @property-read \ManaPHP\LoaderInterface      $loader
 * @property-read \ManaPHP\Html\RendererContext $_context
 */
class Renderer extends Component implements RendererInterface
{
    /**
     * @var \ManaPHP\Html\Renderer\EngineInterface[]
     */
    protected $_resolved = [];

    /**
     * @var array
     */
    protected $_engines
        = [
            '.phtml' => 'ManaPHP\Html\Renderer\Engine\Php',
            '.sword' => 'ManaPHP\Html\Renderer\Engine\Sword'
        ];

    /**
     * @var array array
     */
    protected $_files = [];

    /**
     * @var \ManaPHP\Coroutine\Mutex
     */
    protected $_mutex;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['engines'])) {
            $this->_engines = $options['engines'] ?: ['.phtml' => 'ManaPHP\Html\Renderer\Engine\Php'];
        }

        $this->loader->registerFiles('@manaphp/Html/Renderer/helpers.php');

        $this->_mutex = new Mutex();
    }

    public function lock()
    {
        $this->_mutex->lock();
    }

    public function unlock()
    {
        $this->_mutex->unlock();
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

        if (!$this->_mutex->isLocked()) {
            throw new MisuseException('renderer is not locked');
        }

        if (DIRECTORY_SEPARATOR === '\\' && str_contains($template, '\\')) {
            $template = str_replace('\\', '/', $template);
        }

        if (!str_contains($template, '/')) {
            $template = dirname(end($context->templates)) . '/' . $template;
        }

        $template = $this->alias->resolve($template);

        if (isset($this->_files[$template])) {
            list($file, $extension) = $this->_files[$template];
        } else {
            $file = null;
            $extension = null;
            foreach ($this->_engines as $extension => $engine) {
                if (is_file($tmp = $template . $extension)) {
                    if (PHP_EOL !== "\n") {
                        $realPath = strtr(realpath($tmp), '\\', '/');
                        if ($tmp !== $realPath) {
                            trigger_error("File name ($realPath) case mismatch for $tmp", E_USER_ERROR);
                        }
                    }

                    $file = $tmp;
                    break;
                }
            }

            if (!$file) {
                $extensions = implode(', or ', array_keys($this->_engines));
                throw new FileNotFoundException(['`%s` with `%s` extension was not found', $template, $extensions]);
            }

            $this->_files[$template] = [$file, $extension];
        }

        $engine = $this->_resolved[$extension] ??
            ($this->_resolved[$extension] = $this->getShared($this->_engines[$extension]));

        if (isset($vars['renderer'])) {
            throw new MisuseException('variable `renderer` is reserved for renderer');
        }

        if (isset($vars['di'])) {
            throw new MisuseException('variable `di` is reserved for renderer');
        }

        $context->templates[] = $template;

        $this->fireEvent('renderer:rendering', compact('template', 'file', 'vars'));

        $vars['renderer'] = $this;
        $vars['di'] = $this->_di;

        if ($directOutput) {
            $engine->render($file, $vars);
            $content = null;
        } else {
            ob_start();
            ob_implicit_flush(false);
            try {
                $engine->render($file, $vars);
            } finally {
                $content = ob_get_clean();
            }
        }

        $this->fireEvent('renderer:rendered', compact('template', 'file', 'vars'));

        array_pop($context->templates);

        return $content;
    }

    /**
     * @param string $file
     * @param array  $vars
     *
     * @return string
     */
    public function renderFile($file, $vars = [])
    {
        $this->lock();
        try {
            return $this->render($file, $vars);
        } finally {
            $this->unlock();
        }
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
        if (DIRECTORY_SEPARATOR === '\\' && str_contains($template, '\\')) {
            $template = str_replace('\\', '/', $template);
        }

        if (!str_contains($template, '/')) {
            $template = dirname(end($this->_context->templates)) . '/' . $template;
        }

        $template = $this->alias->resolve($template);

        if (isset($this->_files[$template])) {
            return true;
        }

        foreach ($this->_engines as $extension => $_) {
            if (is_file($file = $template . $extension)) {
                if (PHP_EOL !== "\n") {
                    $realPath = strtr(realpath($file), '\\', '/');
                    if ($file !== $realPath) {
                        trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                    }
                }
                $this->_files[$template] = [$file, $extension];
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

        return $context->sections[$section] ?? $default;
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

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();

        if (isset($data['_context'])) {
            foreach ($data['_context']['sections'] as $k => $v) {
                $data['_context']['sections'][$k] = '***';
            }
        }

        $data['_files'] = ['***'];
        unset($data['_mutex']);

        return $data;
    }
}