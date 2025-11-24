<?php

declare(strict_types=1);

namespace ManaPHP\Viewing;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Rendering\RendererInterface;
use ReflectionClass;
use function array_merge;
use function basename;
use function class_exists;
use function dirname;
use function explode;
use function is_string;
use function preg_replace_callback;
use function str_contains;
use function str_starts_with;
use function ucfirst;

class View implements ViewInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected WidgetFactory $widgetFactory;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RendererInterface $renderer;

    #[Autowired] protected bool $autofix_url = true;
    #[Autowired] protected ?string $layout = '@views/Layouts/Default';

    protected array $dirs = [];
    protected array $exists = [];

    public function getContext(): ViewContext
    {
        return $this->contextManager->getContext($this);
    }

    public function setLayout(string $layout = 'Default'): static
    {
        $context = $this->getContext();

        $context->layout = $layout;

        return $this;
    }

    public function disableLayout(): static
    {
        $context = $this->getContext();

        $context->layout = '';

        return $this;
    }

    public function setVar(string $name, mixed $value): static
    {
        $context = $this->getContext();

        $context->vars[$name] = $value;

        return $this;
    }

    public function setVars(array $vars): static
    {
        $context = $this->getContext();

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    public function getVar(?string $name = null): mixed
    {
        $context = $this->getContext();

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    public function hasVar(string $name): bool
    {
        $context = $this->getContext();

        return isset($context->_vars[$name]);
    }

    public function render(string $template, array $vars = []): string
    {
        $context = $this->getContext();

        if ($vars !== []) {
            $context->vars = $vars;
        }

        if (str_contains($template, '::')) {
            list($controller, $action) = explode('::', $template);
            $rClass = new ReflectionClass($controller);
            $controllerFile = $rClass->getFileName();
            $dir = dirname($controllerFile, 2) . '/Views/' . basename($rClass->getShortName(), 'Controller');

            $this->dirs[$dir] ??= LocalFS::dirExists($dir);

            $action = basename($action, 'Action');

            if ($this->dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                $template = $dir . '/' . ucfirst($action);
            }
        }


        $this->renderer->lock();
        try {
            $context->content = $this->renderer->render($template, $context->vars);

            if ($context->layout === null) {
                $context->content = $this->renderer->render($this->layout, $context->vars);
            } elseif ($context->layout !== '') {
                $context->content = $this->renderer->render($context->layout, $context->vars);
            }
        } finally {
            $this->renderer->unlock();
        }

        if ($this->autofix_url) {
            $this->fixUrl();
        }

        return $context->content;
    }

    public function fixUrl(): void
    {
        if (($prefix = $this->router->getPrefix()) === '') {
            return;
        }

        $context = $this->getContext();

        $context->content = preg_replace_callback(
            '#\b(href|src|action|data-src)=(["\'`]{1,2})/(?!/)#',
            static fn($match) => "$match[1]=$match[2]$prefix/",
            $context->content
        );
    }

    public function widget(string $widget, array $options = []): void
    {
        if (!str_contains($widget, '\\')) {
            $widget = 'App\\Widgets\\' . ucfirst($widget) . 'Widget';
        }

        if (!class_exists($widget)) {
            throw new InvalidValueException('The widget class "{widget}" does not exist.', ['widget' => $widget]);
        }

        $rClass = new ReflectionClass($widget);
        $widgetFile = $rClass->getFileName();
        $view = dirname($widgetFile, 2) . '/Views/Widgets/' . basename($rClass->getShortName(), 'Widget');

        $widgetInstance = $this->widgetFactory->get($widget);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->renderer->render($view, $vars, true);
        }
    }

    public function block(string $path, array $vars = []): void
    {
        if (!str_starts_with($path, '@') && !str_contains($path, '/')) {
            $path = "@views/Blocks/$path";
        }

        $this->renderer->render($path, $vars, true);
    }

    public function setContent(string $content): static
    {
        $context = $this->getContext();

        $context->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->getContext()->content;
    }
}
