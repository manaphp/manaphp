<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\View\Exception as ViewException;

/**
 * Class ManaPHP\Mvc\View
 *
 * @package view
 *
 * @property \ManaPHP\RendererInterface     $renderer
 * @property \ManaPHP\Cache\EngineInterface $viewsCache
 * @property \ManaPHP\Http\RequestInterface $request
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

    /**
     * @var string
     */
    protected $_controllerName;

    /**
     * @var string
     */
    protected $_actionName;

    /**
     * @var string
     */
    protected $_pickedView;

    /**
     * @var string
     */
    protected $_current_template;

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
     * Gets the name of the controller rendered
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * Gets the name of the action rendered
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     * @throws \ManaPHP\Mvc\View\Exception
     */
    public function _render($template, $vars, $directOutput)
    {
        if ($template[0] !== '@') {
            if (strpos($template, '/') !== false) {
                throw new ViewException(['`:template` template can not contains relative path', 'template' => $template]);
            }

            $template = dirname($this->_current_template) . '/' . $template;
        }
        $this->_current_template = $template;

        if (isset($vars['view'])) {
            throw new ViewException('variable `view` is reserved for view'/**m0662b55555fc72f7d*/);
        }
        $vars['view'] = $this;

        if (isset($vars['request'])) {
            throw new ViewException('variable `request` is reserved for view');
        }
        $vars['request'] = isset($this->request) ? $this->request : null;

        return $this->renderer->render($template, $vars, $directOutput);
    }

    /**
     * Executes render process from dispatching data
     *
     *<code>
     * //Shows recent posts view (app/views/posts/recent.phtml)
     * $view->start()->render('posts', 'recent')->finish();
     *</code>
     *
     * @param string $controller
     * @param string $action
     *
     * @return static
     * @throws \ManaPHP\Mvc\View\Exception
     */
    public function render($controller, $action)
    {
        if ($this->_pickedView) {
            $parts = explode('/', $this->_pickedView);
            if (count($parts) === 1) {
                $this->_controllerName = $controller;
                $this->_actionName = $parts[0];
            } else {
                $this->_controllerName = $parts[0];
                $this->_actionName = $parts[1];
            }
        } else {
            $this->_controllerName = $controller;
            $this->_actionName = $action;
        }

        $this->fireEvent('view:beforeRender');

        if (($pos = strpos($this->_controllerName, '/')) !== false) {
            $dir = '@app/Areas/' . substr($this->_controllerName, 0, $pos) . '/Views/' . substr($this->_controllerName, $pos + 1);
        } else {
            $dir = "@app/Views/{$this->_controllerName}";
        }

        if ($this->filesystem->dirExists($dir)) {
            $view = $dir . '/' . ucfirst($this->_actionName);
        } else {
            $view = $dir;
        }

        $this->_content = $this->_render($view, $this->_vars, false);

        if ($this->_layout !== false) {
            if ($this->_layout[0] === '@') {
                $layout = $this->_layout;
            } else {
                if ($pos !== false) {
                    $layout = '@app/Areas/' . substr($this->_controllerName, 0, $pos) . '/Views/Layouts' . substr($this->_controllerName, $pos);
                    if (!$this->filesystem->dirExists(dirname($layout))) {
                        $layout = '@app/Views/Layouts/' . ucfirst($this->_layout ?: 'Default');
                    }
                } else {
                    $layout = '@app/Views/Layouts/' . ucfirst($this->_layout ?: $this->_controllerName);
                }
            }
            $this->_content = $this->_render($layout, $this->_vars, false);
        }

        $this->fireEvent('view:afterRender');

        return $this;
    }

    /**
     * Choose a different view to render instead of last-controller/last-action
     *
     * <code>
     * class ProductsController extends \ManaPHP\Mvc\Controller
     * {
     *
     *    public function saveAction()
     *    {
     *
     *         //Do some save stuff...
     *
     *         //Then show the list view
     *         $this->view->pick("products/list");
     *    }
     * }
     * </code>
     *
     * @param string $view
     *
     * @return static
     */
    public function pick($view)
    {
        $this->_pickedView = $view;

        return $this;
    }

    /**
     * Renders a partial view
     *
     * <code>
     *    //Show a partial inside another view
     *    $this->partial('shared/footer');
     * </code>
     *
     * <code>
     *    //Show a partial inside another view with parameters
     *    $this->partial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string $path
     * @param array  $vars
     *
     * @throws \ManaPHP\Mvc\View\Exception
     */
    public function partial($path, $vars = [])
    {
        $this->_render($path, $vars, true);
    }

    /**
     * @param string    $widget
     * @param array     $options
     * @param int|array $cacheOptions
     *
     * @throws \ManaPHP\Mvc\View\Exception
     */
    public function widget($widget, $options = [], $cacheOptions = null)
    {
        if (($pos = strpos($widget, '/')) === false) {
            $widgetClassName = $this->alias->resolveNS("@ns.app\\Widgets\\{$widget}Widget");
            $view = '@app/Views/Widgets/' . $widget;

            if (!class_exists($widgetClassName) && ($pos = strpos($this->_controllerName, '/')) !== false) {
                $widgetClassName = $this->alias->resolveNS('@ns.app\\Areas\\' . substr($this->_controllerName, 0, $pos) . "\\Widgets\\{$widget}Widget");
                $view = '@app/Areas/' . substr($this->_controllerName, 0, $pos) . '/Views/Widgets/' . $widget;
            }
        } else {
            if ($pos === 0) {
                $widgetClassName = $this->alias->resolveNS('@ns.app\\Widgets\\' . substr($widget, 1) . 'Widget');
                $view = '@app/Views/Widgets/' . substr($widget, 1);
            } else {
                $widgetClassName = $this->alias->resolveNS('@ns.app\\Areas\\' . substr($widget, 0, $pos) . '\\Widgets\\' . substr($widget, $pos + 1) . 'Widget');
                $view = '@app/Areas/' . substr($widget, 0, $pos) . '/Views/Widgets/' . substr($widget, $pos + 1);
            }
        }

        if (!class_exists($widgetClassName)) {
            throw new ViewException(['`:widget` widget is invalid: `:class` class is not exists'/**m020db278f144382d6*/, 'widget' => $widget, 'class' => $widgetClassName]);
        }

        /**
         * @var \ManaPHP\Mvc\WidgetInterface $widgetInstance
         */
        $widgetInstance = $this->_di->get($widgetClassName);
        $vars = $widgetInstance->run($options);

        if ($cacheOptions !== null) {
            $cacheOptions = is_array($cacheOptions) ? $cacheOptions : ['ttl' => $cacheOptions];

            $cacheOptions['key'] = $view . (isset($cacheOptions['key']) ? '/' . $cacheOptions['key'] : '');
            $cacheOptions['key'] = str_replace($this->alias->resolve('@app') . '/', '', $this->alias->resolve($cacheOptions['key']));

            $content = $this->viewsCache->get($cacheOptions['key']);
            if ($content === false) {
                $this->fireEvent('viewsCache:miss', ['key' => $cacheOptions['key'], 'view' => $view]);
                if (is_string($vars)) {
                    $content = $vars;
                } else {
                    $content = $this->_render($view, $vars, false);
                }

                $this->viewsCache->set($cacheOptions['key'], $content, $cacheOptions['ttl']);
            }
            $this->fireEvent('viewsCache:hit', ['key' => $cacheOptions['key'], 'view' => $view]);

            echo $content;
        } else {
            if (is_string($vars)) {
                echo $vars;
            } else {
                $this->_render($view, $vars, true);
            }
        }
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