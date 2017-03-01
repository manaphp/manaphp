<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\View\Exception as ViewException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\View
 *
 * @package view
 *
 * @property \ManaPHP\RendererInterface      $renderer
 * @property \ManaPHP\Cache\AdapterInterface $viewsCache
 * @property \ManaPHP\Http\RequestInterface  $request
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
    protected $_viewVars = [];

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
        $this->_viewVars[$name] = $value;

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
        $this->_viewVars = array_merge($this->_viewVars, $vars);

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
            return $this->_viewVars;
        } else {
            return isset($this->_viewVars[$name]) ? $this->_viewVars[$name] : null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name)
    {
        return isset($this->_viewVars[$name]);
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
        if ($this->_controllerName === null) {
            $this->_controllerName = $controller;
        }

        if ($this->_actionName === null) {
            $this->_actionName = $action;
        }

        $this->fireEvent('view:beforeRender');

        $this->_content = $this->_render("@views/{$this->_controllerName}/" . ucfirst($this->_actionName), $this->_viewVars, false);

        if ($this->_layout !== false) {
            $this->_content = $this->_render('@views/Layouts/' . ucfirst($this->_layout ?: $this->_controllerName), $this->_viewVars, false);
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
        list($this->_controllerName, $this->_actionName) = array_pad(explode('/', $view), -2, null);

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
     * @throws \ManaPHP\Renderer\Exception
     */
    public function partial($path, $vars = [])
    {
        if (!Text::contains($path, '/')) {
            $path = $this->_controllerName . '/' . $path;
        }

        $this->_render($path[0] === '@' ? $path : "@views/$path", $vars, true);
    }

    /**
     * @param string    $widget
     * @param array     $options
     * @param int|array $cacheOptions
     *
     * @throws \ManaPHP\Mvc\View\Exception
     * @throws \ManaPHP\Di\Exception
     */
    public function widget($widget, $options = [], $cacheOptions = null)
    {
        $widgetClassName = $this->alias->resolve("@ns.widgets\\{$widget}Widget");

        if (!class_exists($widgetClassName)) {
            throw new ViewException('`:widget` widget is invalid: `:class` class is not exists'/**m020db278f144382d6*/, ['widget' => $widget, 'class' => $widgetClassName]);
        }

        /**
         * @var \ManaPHP\Mvc\WidgetInterface $widgetInstance
         */
        $widgetInstance = $this->_dependencyInjector->get($widgetClassName);
        $vars = $widgetInstance->run($options);

        $view = '@views/Widgets/' . $widget;

        if ($cacheOptions !== null) {
            $cacheOptions = is_array($cacheOptions) ? $cacheOptions : ['ttl' => $cacheOptions];

            $cacheOptions['key'] = $view . (isset($cacheOptions['key']) ? '/' . $cacheOptions['key'] : '');
            $cacheOptions['key'] = str_replace($this->alias->resolve('@app') . '/', '', $this->alias->resolve($cacheOptions['key']));

            $content = $this->viewsCache->get($cacheOptions['key']);
            if ($content === false) {
                if (is_string($vars)) {
                    $content = $vars;
                } else {
                    $content = $this->_render($view, $vars, false);
                }

                $this->viewsCache->set($cacheOptions['key'], $content, $cacheOptions['ttl']);
            }

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
     *    $this->view->setContent("<h1>hello</h1>");
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