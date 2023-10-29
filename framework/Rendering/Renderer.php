<?php
declare(strict_types=1);

namespace ManaPHP\Rendering;

use ManaPHP\AliasInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Coroutine\Mutex;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Rendering\Renderer\Event\RendererRendering;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Renderer implements RendererInterface
{
    use ContextTrait;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected ContainerInterface $container;

    #[Autowired] protected array $engines
        = ['.phtml' => 'ManaPHP\Rendering\Engine\Php',
           '.sword' => 'ManaPHP\Rendering\Engine\Sword'];

    protected array $files = [];
    protected Mutex $mutex;

    protected function getMutex(): Mutex
    {
        return $this->mutex ??= new Mutex();
    }

    public function lock(): void
    {
        $this->getMutex()->lock();
    }

    public function unlock(): void
    {
        $this->getMutex()->unlock();
    }

    public function render(string $template, array $vars = [], bool $directOutput = false): ?string
    {
        /** @var RendererContext $context */
        $context = $this->getContext();

        if (!$this->getMutex()->isLocked()) {
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
                throw new FileNotFoundException(['`{1}` with `{2}` extension was not found', $template, $extensions]);
            }

            $this->files[$template] = [$file, $extension];
        }

        /** @var EngineInterface $engine */
        $engine = $this->container->get($this->engines[$extension]);

        if (isset($vars['renderer'])) {
            throw new MisuseException('variable `renderer` is reserved for renderer');
        }

        $context->templates[] = $template;

        $this->eventDispatcher->dispatch(new RendererRendering($this, $template, $file, $vars));

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

        $this->eventDispatcher->dispatch(new RendererRendering($this, $template, $file, $vars));

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
        /** @var RendererContext $context */
        $context = $this->getContext();

        if (DIRECTORY_SEPARATOR === '\\' && str_contains($template, '\\')) {
            $template = str_replace('\\', '/', $template);
        }

        if (!str_contains($template, '/')) {
            $template = dirname(end($context->templates)) . '/' . $template;
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
        /** @var RendererContext $context */
        $context = $this->getContext();

        return $context->sections[$section] ?? $default;
    }

    public function startSection(string $section, ?string $default = null): void
    {
        /** @var RendererContext $context */
        $context = $this->getContext();

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
        /** @var RendererContext $context */
        $context = $this->getContext();

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
        /** @var RendererContext $context */
        $context = $this->getContext();

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