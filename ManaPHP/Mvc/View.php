<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class ViewContext
{
    /**
     * @var int
     */
    public $max_age;

    /**
     * @var false|string|null
     */
    public $layout;

    /**
     * @var array
     */
    public $vars = [];

    /**
     * @var string
     */
    public $content;
}

/**
 * @property-read \ManaPHP\Html\RendererInterface   $renderer
 * @property-read \ManaPHP\Http\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\Mvc\ViewContext          $context
 */
class View extends Component implements ViewInterface
{
    /**
     * @var int
     */
    protected $max_age;

    /**
     * @var bool
     */
    protected $autofix_url = true;

    /**
     * @var array
     */
    protected $dirs = [];

    /**
     * @var array
     */
    protected $exists_cache;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['max_age'])) {
            $this->max_age = (int)$options['max_age'];
        }

        if (isset($options['autofix_url'])) {
            $this->autofix_url = (bool)$options['autofix_url'];
        }
    }

    /**
     * @param int $max_age
     *
     * @return static
     */
    public function setMaxAge($max_age)
    {
        $this->context->max_age = (int)$max_age;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxAge()
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

    /**
     * @param false|string $layout
     *
     * @return static
     */
    public function setLayout($layout = 'Default')
    {
        $context = $this->context;

        $context->layout = $layout;

        return $this;
    }

    /**
     * Set a single view parameter
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value)
    {
        $context = $this->context;

        $context->vars[$name] = $value;

        return $this;
    }

    /**
     * Adds parameters to view
     *
     * @param array $vars
     *
     * @return static
     */
    public function setVars($vars)
    {
        $context = $this->context;

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getVar($name = null)
    {
        $context = $this->context;

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name)
    {
        $context = $this->context;

        return isset($context->_vars[$name]);
    }

    /**
     * @return false|string
     */
    protected function findLayout()
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

    /**
     * Executes render process from dispatching data
     *
     * @param string $template
     * @param array  $vars
     *
     * @return string
     */
    public function render($template = null, $vars = [])
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

            if (!isset($this->dirs[$dir])) {
                $this->dirs[$dir] = LocalFS::dirExists($dir);
            }

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

    /**
     * @return void
     */
    public function fixUrl()
    {
        if (($base_url = $this->alias->get('@web') ?? '') === '') {
            return;
        }

        $context = $this->context;

        $context->content = preg_replace_callback(
            '#\b(href|src|action|data-src)=(["\'`]{1,2})/(?!/)#',
            static function ($match) use ($base_url) {
                return "$match[1]=$match[2]{$base_url}/";
            }, $context->content
        );
    }

    /**
     * @param string $template
     *
     * @return bool
     */
    public function exists($template = null)
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

            if (!isset($this->dirs[$dir])) {
                $this->dirs[$dir] = LocalFS::dirExists($dir);
            }

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

    /**
     * @param string $widget
     *
     * @return string|false
     */
    public function getWidgetClassName($widget)
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

    /**
     * @param string $widget
     * @param array  $options
     *
     * @return void
     */
    public function widget($widget, $options = [])
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

        /** @var \ManaPHP\Mvc\View\WidgetInterface $widgetInstance */
        $widgetInstance = $this->getShared($widgetClassName);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->renderer->render($view, $vars, true);
        }
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function block($path, $vars = [])
    {
        if ($path[0] !== '@' && !str_contains($path, '/')) {
            $path = "@views/Blocks/$path";
        }

        $this->renderer->render($path, $vars, true);
    }

    /**
     * Externally sets the view content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $context = $this->context;

        $context->content = $content;

        return $this;
    }

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->context->content;
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();

        $data['context']['content'] = '***';
        unset($data['exists_cache']);

        return $data;
    }
}