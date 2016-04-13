<?php

namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Mvc\View\Exception;

    /**
     * ManaPHP\Mvc\View
     *
     * ManaPHP\Mvc\View is a class for working with the "view" portion of the model-view-controller pattern.
     * That is, it exists to help keep the view script separate from the model and controller scripts.
     * It provides a system of helpers, output filters, and variable escaping.
     *
     * <code>
     * //Setting views directory
     * $view = new ManaPHP\Mvc\View();
     * $view->setViewsDir('app/views/');
     *
     * $view->start();
     * //Shows recent posts view (app/views/posts/recent.phtml)
     * $view->render('posts', 'recent');
     * $view->finish();
     *
     * //Printing views output
     * echo $view->getContent();
     * </code>
     */
    class View extends Component implements ViewInterface
    {
        /**
         * @var string
         */
        protected $_content = null;

        /**
         * @var array
         */
        protected $_viewVars = [];

        /**
         * @var string
         */
        protected $_AppDir = null;

        /**
         * @var string
         */
        protected $_rootNamespace = null;

        /**
         * @var false|string|null
         */
        protected $_layout = null;

        /**
         * @var string
         */
        protected $_moduleName;

        /**
         * @var string
         */
        protected $_controllerName;

        /**
         * @var string
         */
        protected $_actionName;

        /**
         *
         * @param string $appDir
         *
         * @return static
         */
        public function setAppDir($appDir)
        {
            $this->_AppDir = str_replace('\\', '/', rtrim($appDir, '\\/'));
            $this->_rootNamespace = basename($this->_AppDir);

            return $this;
        }

        /**
         * Gets views directory
         *
         * @return string
         */
        public function getAppDir()
        {
            return $this->_AppDir;
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
            $this->_viewVars[$name] = $value;

            return $this;
        }

        /**
         * Adds parameters to view
         *
         * @param $vars
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
        public function getVar($name)
        {
            return isset($this->_viewVars[$name]) ? $this->_viewVars[$name] : null;
        }

        /**
         * @return array
         */
        public function getVars()
        {
            return $this->_viewVars;
        }

        /**
         * Gets the name of the module rendered
         *
         * @return string
         */
        public function getModuleName()
        {
            return $this->_moduleName;
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
         * Executes render process from dispatching data
         *
         *<code>
         * //Shows recent posts view (app/views/posts/recent.phtml)
         * $view->start()->render('posts', 'recent')->finish();
         *</code>
         *
         * @param string $module
         * @param string $controller
         * @param string $action
         *
         * @return static
         * @throws \ManaPHP\Mvc\View\Exception
         */
        public function render($module, $controller, $action)
        {
            if ($this->_moduleName === null) {
                $this->_moduleName = $module;
            }

            if ($this->_controllerName === null) {
                $this->_controllerName = $controller;
            }

            if ($this->_actionName === null) {
                $this->_actionName = $action;
            }

            $this->fireEvent('view:beforeRender');

            $view = "/{$this->_moduleName}/Views/{$this->_controllerName}/" . ucfirst($this->_actionName);

            $this->_content = $this->renderer->render($this->_AppDir . $view, $this->_viewVars, false);

            if ($this->_layout !== false) {
                if (is_string($this->_layout)) {
                    $layout = $this->_layout;
                } else {
                    $layout = $this->_controllerName;
                }

                $view = "/$this->_moduleName/Views/Layouts/" . ucfirst($layout);
                $this->_content = $this->renderer->render($this->_AppDir . $view, $this->_viewVars, false);
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
            list($this->_moduleName, $this->_controllerName, $this->_actionName) = array_pad(explode('/', $view), -3, null);

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
            if (strpos($path, '/') === false) {
                $path = $this->_controllerName . '/' . $path;
            }

            $view = "/$this->_moduleName/Views/$path";

            $this->renderer->render($this->_AppDir . $view, array_merge($this->_viewVars, $vars), true);
        }

        public function widget($widget, $options = [])
        {
            $widgetClassName = "{$this->_rootNamespace}\\{$this->_moduleName}\\Widgets\\{$widget}Widget";

            if (!class_exists($widgetClassName)) {
                throw new Exception("widget '$widget' is not exist: " . $widgetClassName);
            }

            /**
             * @var \ManaPHP\Mvc\WidgetInterface $widgetInstance
             */
            $widgetInstance = $this->_dependencyInjector->get($widgetClassName);
            $vars = $widgetInstance->run($options);
            if (is_string($vars)) {
                echo $vars;
            } else {
                $view = "/$this->_moduleName/Views/Widgets/" . $widget;
                $this->renderer->render($this->_AppDir . $view, $vars, true);
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
    }
}
