<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \Psr\Container\ContainerInterface         $container
 * @property-read \ManaPHP\AliasInterface                   $alias
 * @property-read \ManaPHP\Rendering\RendererInterface      $renderer
 * @property-read \ManaPHP\Http\DispatcherInterface         $dispatcher
 * @property-read \ManaPHP\Mvc\View\Widget\FactoryInterface $widgetFactory
 * @property-read \ManaPHP\Mvc\ViewContext                  $context
 */
class View extends Component implements ViewInterface
{
    protected int $max_age;
    protected bool $autofix_url;

    protected array $dirs = [];
    protected array $exists_cache;

    public function __construct(int $max_age = 0, bool $autofix_url = true)
    {
        $this->max_age = $max_age;
        $this->autofix_url = $autofix_url;
    }

    public function setMaxAge(int $max_age): static
    {
        $this->context->max_age = $max_age;

        return $this;
    }

    public function getMaxAge(): int
    {
        if ($this->max_age > 0) {
            $context = $this->context;
            if ($context->max_age === null) {
                return $this->max_age;
            } else {
                return $context->max_age > 0 ? $context->max_age : 0;
            }
        } else {
            return 0;
        }
    }

    public function setLayout(false|string $layout = 'Default'): static
    {
        $context = $this->context;

        $context->layout = $layout;

        return $this;
    }

    public function setVar(string $name, mixed $value): static
    {
        $context = $this->context;

        $context->vars[$name] = $value;

        return $this;
    }

    public function setVars(array $vars): static
    {
        $context = $this->context;

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    public function getVar(?string $name = null): mixed
    {
        $context = $this->context;

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    public function hasVar(string $name): bool
    {
        $context = $this->context;

        return isset($context->_vars[$name]);
    }

    protected function findLayout(): false|string
    {
        $context = $this->context;

        if ($context->layout === null) {
            $controller = $this->dispatcher->getController();
            if ($area = $this->dispatcher->getArea()) {
                if ($this->renderer->exists("@app/Areas/$area/Views/Layouts/$controller")) {
                    $layout = "@app/Areas/$area/Views/Layouts/$controller";
                } elseif ($this->renderer->exists("@app/Areas/$area/Views/Layouts/Default")) {
                    $layout = "@app/Areas/$area/Views/Layouts/Default";
                } else {
                    $layout = '@views/Layouts/Default';
                }
            } else {
                if ($this->renderer->exists("@views/Layouts/$controller")) {
                    $layout = "@views/Layouts/$controller";
                } else {
                    $layout = '@views/Layouts/Default';
                }
            }
        } elseif (is_string($context->layout)) {
            $layout = $context->layout;
            if ($layout[0] !== '@') {
                $layout = ucfirst($layout);
                if (($area = $this->dispatcher->getArea())
                    && $this->renderer->exists("@app/Areas/$area/Views/Layouts/$layout")
                ) {
                    $layout = "@app/Areas/$area/Views/Layouts/$layout";
                } else {
                    $layout = "@views/Layouts/$layout";
                }
            }
        } else {
            $layout = false;
        }

        return $layout;
    }

    public function render(?string $template = null, array $vars = []): string
    {
        $context = $this->context;

        if ($vars !== []) {
            $context->vars = $vars;
            $this->setMaxAge(0);
        }

        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

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

        $this->fireEvent('view:rendering');

        $this->renderer->lock();
        try {
            $context->content = $this->renderer->render($template, $context->vars, false);

            if ($context->layout !== false) {
                $layout = $this->findLayout();
                $context->content = $this->renderer->render($layout, $context->vars, false);
            }
        } finally {
            $this->renderer->unlock();
        }

        $this->fireEvent('view:rendered');

        if ($this->autofix_url) {
            $this->fixUrl();
        }

        return $context->content;
    }

    public function fixUrl(): void
    {
        if (($base_url = $this->alias->get('@web') ?? '') === '') {
            return;
        }

        $context = $this->context;

        $context->content = preg_replace_callback(
            '#\b(href|src|action|data-src)=(["\'`]{1,2})/(?!/)#',
            static fn($match) => "$match[1]=$match[2]{$base_url}/",
            $context->content
        );
    }

    public function exists(?string $template = null): bool
    {
        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

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

        return $this->exists_cache[$template] ??
            ($this->exists_cache[$template] = $this->renderer->exists($template));
    }

    public function getWidgetClassName(string $widget): false|string
    {
        if (str_contains($widget, '/')) {
            throw new MisuseException(['it is not allowed to access other area `:widget` widget', 'widget' => $widget]);
        }

        $area = $this->dispatcher->getArea();
        if ($area && class_exists($widgetClassName = "App\\Areas\\$area\\Widgets\\{$widget}Widget")) {
            return $widgetClassName;
        }

        return class_exists($widgetClassName = "App\\Widgets\\{$widget}Widget") ? $widgetClassName : false;
    }

    public function widget(string $widget, array $options = []): void
    {
        if ($options !== []) {
            $this->setMaxAge(0);
        }

        if (!$widgetClassName = $this->getWidgetClassName($widget)) {
            throw new InvalidValueException(['`%s` class is not exists', $widgetClassName]);
        }

        if (str_contains($widgetClassName, '\\Areas\\')) {
            $view = "@app/Areas/{$this->dispatcher->getArea()}/Views/Widgets/$widget";
        } else {
            $view = "@views/Widgets/$widget";
        }

        $widgetInstance = $this->widgetFactory->get($widgetClassName);
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
        $context = $this->context;

        $context->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->context->content;
    }

    public function dump(): array
    {
        $data = parent::dump();

        $data['context']['content'] = '***';
        unset($data['exists_cache']);

        return $data;
    }
}