<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Mvc\View\Event\ViewRendered;
use ManaPHP\Mvc\View\Event\ViewRendering;
use ManaPHP\Rendering\RendererInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function is_string;
use function preg_match;

class View implements ViewInterface
{
    use ContextTrait;

    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RendererInterface $renderer;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected int $max_age = 0;
    #[Autowired] protected bool $autofix_url = true;
    #[Autowired] protected ?string $layout = '@views/Layouts/Default';

    protected array $dirs = [];
    protected array $exists = [];

    public function setMaxAge(int $max_age): static
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        $context->max_age = $max_age;

        return $this;
    }

    public function getMaxAge(): int
    {
        if ($this->max_age > 0) {
            /** @var ViewContext $context */
            $context = $this->getContext();

            if ($context->max_age === null) {
                return $this->max_age;
            } else {
                return $context->max_age > 0 ? $context->max_age : 0;
            }
        } else {
            return 0;
        }
    }

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

    protected function explodeHandler(string $handler): array
    {
        list($controllerClass, $action) = explode('::', $handler);
        if (str_starts_with($controllerClass, 'App\\Controllers\\')) {
            return [null,
                    basename(substr($controllerClass, strrpos($controllerClass, '\\') + 1), 'Controller'),
                    basename($action, 'Action')];
        } elseif (preg_match("#^App\\\\Areas\\\\(\w+)\\\Controllers\\\\(\w+)Controller$#", $controllerClass, $match)
            === 1
        ) {
            return [$match[1], $match[2], basename($action, 'Action')];
        } else {
            return [null, null, null];
        }
    }

    public function render(?string $template = null, array $vars = []): string
    {
        /** @var ViewContext $context */
        $context = $this->getContext();

        if ($vars !== []) {
            $context->vars = $vars;
            $this->setMaxAge(0);
        }

        list($area, $controller, $action1) = $this->explodeHandler($this->dispatcher->getHandler());

        if ($template === null) {
            $action = $action1;
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            $this->dirs[$dir] ??= LocalFS::dirExists($dir);

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

    public function exists(?string $template = null): bool
    {
        list($area, $controller, $action1) = $this->explodeHandler($this->dispatcher->getHandler());

        if ($template === null) {
            $action = $action1;
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            $this->dirs[$dir] ??= LocalFS::dirExists($dir);

            if ($this->dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                return false;
            }
        }

        return $this->exists[$template] ??
            ($this->exists[$template] = $this->renderer->exists($template));
    }

    public function getWidgetClassName(string $widget): ?string
    {
        if (str_contains($widget, '/')) {
            throw new MisuseException(['it is not allowed to access other area `{widget}` widget', 'widget' => $widget]
            );
        }

        list($area) = $this->explodeHandler($this->dispatcher->getHandler());
        if ($area && class_exists($widgetClassName = "App\\Areas\\$area\\Widgets\\{$widget}Widget")) {
            return $widgetClassName;
        }

        return class_exists($widgetClassName = "App\\Widgets\\{$widget}Widget") ? $widgetClassName : null;
    }

    public function widget(string $widget, array $options = []): void
    {
        if ($options !== []) {
            $this->setMaxAge(0);
        }

        if (!$widgetClassName = $this->getWidgetClassName($widget)) {
            throw new InvalidValueException(['`{1}` class is not exists', $widgetClassName]);
        }

        if (preg_match('#App\\\\Areas\\\\(\w+)\\\\#', $widgetClassName, $match) === 1) {
            $area = $match[1];
            $view = "@app/Areas/$area/Views/Widgets/$widget";
        } else {
            $view = "@views/Widgets/$widget";
        }

        $widgetInstance = $this->container->get($widgetClassName);
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