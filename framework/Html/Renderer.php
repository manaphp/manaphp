<?php
declare(strict_types=1);

namespace ManaPHP\Html;

use ManaPHP\Component;
use ManaPHP\Coroutine\Mutex;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\PreconditionException;

/**
 * @property-read \ManaPHP\AliasInterface                        $alias
 * @property-read \ManaPHP\Html\Renderer\Engine\FactoryInterface $engineFactory
 * @property-read \ManaPHP\Html\RendererContext                  $context
 */
class Renderer extends Component implements RendererInterface
{
    /**
     * @var \ManaPHP\Html\Renderer\EngineInterface[]
     */
    protected array $resolved = [];

    protected array $engines;

    protected array $files = [];
    protected Mutex $mutex;

    public function __construct(array $engines
    = ['.phtml' => 'ManaPHP\Html\Renderer\Engine\Php', '.sword' => 'ManaPHP\Html\Renderer\Engine\Sword']
    ) {
        $this->engines = $engines;

        $this->mutex = new Mutex();
    }

    public function lock(): void
    {
        $this->mutex->lock();
    }

    public function unlock(): void
    {
        $this->mutex->unlock();
    }

    public function render(string $template, array $vars = [], bool $directOutput = false): ?string
    {
        $context = $this->context;

        if (!$this->mutex->isLocked()) {
            throw new MisuseException('renderer is not locked');
        }

        if (DIRECTORY_SEPARATOR === '\\' && str_contains($template, '\\')) {
            $template = str_replace('\\', '/', $template);
        }

        if (!str_contains($template, '/')) {
            $template = dirname(end($context->templates)) . '/' . $template;
        }

        $template = $this->alias->resolve($template);

        if (isset($this->files[$template])) {
            list($file, $extension) = $this->files[$template];
        } else {
            $file = null;
            $extension = null;
            foreach ($this->engines as $extension => $engine) {
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
                $extensions = implode(', or ', array_keys($this->engines));
                throw new FileNotFoundException(['`%s` with `%s` extension was not found', $template, $extensions]);
            }

            $this->files[$template] = [$file, $extension];
        }

        $engine = $this->resolved[$extension] ??
            ($this->resolved[$extension] = $this->engineFactory->get($this->engines[$extension]));

        if (isset($vars['renderer'])) {
            throw new MisuseException('variable `renderer` is reserved for renderer');
        }

        $context->templates[] = $template;

        $this->fireEvent('renderer:rendering', compact('template', 'file', 'vars'));

        $vars['renderer'] = $this;

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

    public function renderFile(string $file, array $vars = []): string
    {
        $this->lock();
        try {
            return $this->render($file, $vars);
        } finally {
            $this->unlock();
        }
    }

    public function partial(string $path, array $vars = []): void
    {
        $this->render($path, $vars, true);
    }

    public function exists(string $template): bool
    {
        if (DIRECTORY_SEPARATOR === '\\' && str_contains($template, '\\')) {
            $template = str_replace('\\', '/', $template);
        }

        if (!str_contains($template, '/')) {
            $template = dirname(end($this->context->templates)) . '/' . $template;
        }

        $template = $this->alias->resolve($template);

        if (isset($this->files[$template])) {
            return true;
        }

        foreach ($this->engines as $extension => $_) {
            if (is_file($file = $template . $extension)) {
                if (PHP_EOL !== "\n") {
                    $realPath = strtr(realpath($file), '\\', '/');
                    if ($file !== $realPath) {
                        trigger_error("File name ($realPath) case mismatch for $file", E_USER_ERROR);
                    }
                }
                $this->files[$template] = [$file, $extension];
                return true;
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
    public function getSection(string $section, string $default = ''): string
    {
        $context = $this->context;

        return $context->sections[$section] ?? $default;
    }

    public function startSection(string $section, ?string $default = null): void
    {
        $context = $this->context;

        if ($default === null) {
            ob_start();
            ob_implicit_flush(false);
            $context->stack[] = $section;
        } else {
            $context->sections[$section] = $default;
        }
    }

    public function stopSection(bool $overwrite = false): void
    {
        $context = $this->context;

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

    public function appendSection(): void
    {
        $context = $this->context;

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

    public function dump(): array
    {
        $data = parent::dump();

        if (isset($data['context'])) {
            foreach ($data['context']['sections'] as $k => $v) {
                $data['context']['sections'][$k] = '***';
            }
        }

        $data['files'] = ['***'];
        unset($data['mutex']);

        return $data;
    }
}