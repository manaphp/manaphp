<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;

/**
 * Class ManaPHP\View
 *
 * @package view
 *
 * @property-read \ManaPHP\RendererInterface   $renderer
 * @property-read \ManaPHP\DispatcherInterface $dispatcher
 */
class View extends Component implements ViewInterface
{
    /**
     * @var string
     */
    protected $_content;

    /**
     * @var array
     */
    protected $_vars = [];

    /**
     * @var false|string|null
     */
    protected $_layout;

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_content = null;
        $this->_vars = [];
        $this->_layout = null;
    }

    /**
     * @param false|string $layout
     *
     * @return static
     */
    public function setLayout($layout = 'Default')
    {
        $this->_layout = $layout;

        return $this;
    }

    /**
     * Set a single view parameter
     *
     *<code>
     *    $this->view->setVar('products', $products);
     *</code>
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value)
    {
        $this->_vars[$name] = $value;

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
        $this->_vars = array_merge($this->_vars, $vars);

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
        if ($name === null) {
            return $this->_vars;
        } else {
            return isset($this->_vars[$name]) ? $this->_vars[$name] : null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name)
    {
        return isset($this->_vars[$name]);
    }

    /**
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     */
    public function _render($template, $vars, $directOutput)
    {
        if (isset($vars['view'])) {
            throw new MisuseException('variable `view` is reserved for view');
        }
        $vars['view'] = $this;

        if (isset($vars['request'])) {
            throw new MisuseException('variable `request` is reserved for view');
        }
        $vars['request'] = isset($this->request) ? $this->request : null;

        return $this->renderer->render($template, $vars, $directOutput);
    }

    /**
     * @return string
     */
    protected function _findLayout()
    {
        if ($this->_layout === null) {
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
                $layout = $this->renderer->exists("@views/Layouts/$controller") ? "@views/Layouts/$controller" : '@views/Layouts/Default';
            }
        } elseif (is_string($this->_layout)) {
            $layout = $this->_layout;
            if ($layout[0] !== '@') {
                $layout = ucfirst($layout);
                if (($area = $this->dispatcher->getArea()) && $this->renderer->exists("@app/Areas/$area/Views/Layouts/$layout")) {
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
    public function render($template = null, $vars = null)
    {
        if ($vars !== null) {
            $this->_vars = $vars;
        }

        $area = $this->dispatcher->getArea();
        $controller = $this->dispatcher->getController();

        if (!$template) {
            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            if ($this->filesystem->dirExists($dir)) {
                $template = $dir . '/' . ucfirst($this->dispatcher->getAction());
            } else {
                $template = $dir;
            }
        }

        $this->fireEvent('view:beforeRender');

        $this->_content = $this->_render($template, $this->_vars, false);

        if ($this->_layout !== false) {
            $layout = $this->_findLayout();
            $this->_content = $this->_render($layout, $this->_vars, false);
        }

        $this->fireEvent('view:afterRender');

        return $this->_content;
    }

    /**
     * Renders a partial view
     *
     * @param string $path
     * @param array  $vars
     */
    public function partial($path, $vars = [])
    {
        $this->_render($path, $vars, true);
    }

    /**
     * @param string $widget
     *
     * @return string|false
     */
    public function getWidgetClassName($widget)
    {
        if (strpos($widget, '/') !== false) {
            throw new MisuseException(['it is not allowed to access other area `:widget` widget', 'widget' => $widget]);
        }

        $area = $this->dispatcher->getArea();
        if ($area && class_exists($widgetClassName = $this->alias->resolveNS("@ns.app\\Areas\\$area\\Widgets\\{$widget}Widget"))) {
            return $widgetClassName;
        }

        return class_exists($widgetClassName = $this->alias->resolveNS("@ns.app\\Widgets\\{$widget}Widget")) ? $widgetClassName : false;
    }

    /**
     * @param string $widget
     * @param array  $options
     */
    public function widget($widget, $options = [])
    {
        if (!$widgetClassName = $this->getWidgetClassName($widget)) {
            throw new InvalidValueException(['`:widget` widget is invalid: `:class` class is not exists', 'widget' => $widget, 'class' => $widgetClassName]);
        }

        if (strpos($widgetClassName, '\\Areas\\')) {
            $view = "@app/Areas/{$this->dispatcher->getArea()}/Views/Widgets/$widget";
        } else {
            $view = "@views/Widgets/$widget";
        }

        /**
         * @var \ManaPHP\View\WidgetInterface $widgetInstance
         */
        $widgetInstance = $this->_di->get($widgetClassName);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->_render($view, $vars, true);
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
        if ($path[0] !== '@' && strpos($path, '/') === false) {
            $path = "@views/Blocks/$path";
        }

        $this->_render($path, $vars, true);
    }

    /**
     * Externally sets the view content
     *
     *<code>
     *    $this->view->setContent(" < h1>hello </h1 > ");
     *</code>
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $this->_content = $content;

        return $this;
    }

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    public function dump()
    {
        $data = parent::dump();
        unset($data['_content']);

        return $data;
    }
}