<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Mvc\View\Event\ViewRendered;
use ManaPHP\Mvc\View\Event\ViewRendering;
use ManaPHP\Rendering\RendererInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use function basename;
use function class_exists;
use function dirname;
use function explode;
use function is_string;
use function sprintf;
use function str_contains;
use function ucfirst;

class View implements ViewInterface
{
    use ContextTrait;

    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RendererInterface $renderer;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected bool $autofix_url = true;
    #[Autowired] protected ?string $layout = '@views/Layouts/Default';

    protected array $dirs = [];
    protected array $exists = [];

    public function setLayout(string $layout = 'Default'): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->layout = $layout;

        return $this;
    }

    public function disableLayout(): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->layout = '';

        return $this;
    }

    public function setVar(string $name, mixed $value): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->vars[$name] = $value;

        return $this;
    }

    public function setVars(array $vars): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    public function getVar(?string $name = null): mixed
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    public function hasVar(string $name): bool
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        return isset($context->_vars[$name]);
    }

    public function render(string $template, array $vars = []): string
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        if ($vars !== []) {
            $context->vars = $vars;
            $this->setMaxAge(0);
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

        $this->eventDispatcher->dispatch(new ViewRendering($this));

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

        $this->eventDispatcher->dispatch(new ViewRendered($this));

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

        /** @var ViewContext $context */
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
            throw new InvalidValueException(sprintf('`%s` widget class is not exists', $widget));
        }

        $rClass = new ReflectionClass($widget);
        $widgetFile = $rClass->getFileName();
        $view = dirname($widgetFile, 2) . '/Widgets/' . basename($rClass->getShortName(), 'Widget');

        $widgetInstance = $this->container->get($widget);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->renderer->render($view, $vars, true);
        }
    }

    public function block(string $path, array $vars = []): void
    {
        if ($path[0] !== '@' && !str_contains($path, '/')) {
            $path = "@views/Blocks/$path";
        }

        $this->renderer->render($path, $vars, true);
    }

    public function setContent(string $content): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        return $context->content;
    }
}