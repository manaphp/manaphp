<?php

namespace ManaPHP\Mvc {

    use ManaPHP\ApplicationInterface;
    use ManaPHP\Component;
    use ManaPHP\Di\FactoryDefault;
    use ManaPHP\Http\ResponseInterface;
    use ManaPHP\Mvc\Application\Exception;
    use ManaPHP\Utility\Text;

    /**
     * ManaPHP\Mvc\Application
     *
     * This component encapsulates all the complex operations behind instantiating every component
     * needed and integrating it with the rest to allow the MVC pattern to operate as desired.
     */
    class Application extends Component implements ApplicationInterface
    {
        /**
         * @var string
         */
        protected $_appPath;

        /**
         * @var string
         */
        protected $_appNamespace;

        /**
         * @var string
         */
        protected $_dataPath;

        /**
         * @var boolean
         */
        protected $_implicitView = true;

        /**
         * \ManaPHP\Mvc\Application
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         */
        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();
            $this->_dependencyInjector->setShared('application', $this);

            $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $caller = $traces[0];
            $appClass = get_class($caller['object']);

            if (!Text::startsWith($appClass, 'ManaPHP\\')) {
                $appFile = '/' . str_replace('\\', '/', $appClass) . '.php';
                foreach (get_included_files() as $file) {
                    $file = str_replace('\\', '/', $file);

                    if (Text::contains($file, $appFile)) {
                        $root = str_replace($appFile, '', $file);
                        list($this->_appNamespace) = explode('\\', $appClass, 2);
                        $this->_appPath = $root . '/' . $this->_appNamespace;
                        $this->_dataPath = $root . '/Data';

                        $this->loader->registerNamespaces([$this->_appNamespace => $this->_appPath]);

                        break;
                    }
                }
            }
        }

        /**
         * @return string
         */
        public function getAppDir()
        {
            return $this->_appPath;
        }

        /**
         * @return string
         */
        public function getAppNamespace()
        {
            return $this->_appNamespace;
        }

        /**
         *
         */
        public function getDataDir()
        {
            return $this->_dataPath;
        }

        /**
         * By default. The view is implicitly buffering all the output
         * You can full disable the view component using this method
         *
         * @param boolean $implicitView
         *
         * @return static
         */
        public function useImplicitView($implicitView)
        {
            $this->_implicitView = $implicitView;

            return $this;
        }

        /**
         * Handles a MVC request
         *
         * @param string $uri
         *
         * @return \ManaPHP\Http\ResponseInterface|boolean
         * @throws \ManaPHP\Mvc\Application\Exception|\ManaPHP\Event\Exception|\ManaPHP\Di\Exception|\ManaPHP\Mvc\Application\NotFoundModuleException|\ManaPHP\Mvc\Dispatcher\Exception|\ManaPHP\Mvc\Dispatcher\NotFoundControllerException|\ManaPHP\Mvc\Dispatcher\NotFoundActionException
         */
        public function handle($uri = null)
        {
            if ($this->fireEvent('application:boot') === false) {
                return false;
            }

            $router = $this->_dependencyInjector->getShared('router');

            $router->handle($uri, null, false);

            $moduleName = ucfirst($router->getModuleName());
            $controllerName = $router->getControllerName();
            $actionName = $router->getActionName();
            $params = $router->getParams();

            $moduleClassName = $this->getAppNamespace() . "\\$moduleName\\Module";

            $moduleObject = null;

            $this->fireEvent('application:beforeStartModule', $moduleName);
            $moduleObject = $this->_dependencyInjector->getShared($moduleClassName);
            $moduleObject->registerAutoloaders($this->_dependencyInjector);
            $moduleObject->registerServices($this->_dependencyInjector);
            $this->fireEvent('application:afterStartModule', $moduleObject);

            $dispatcher = $this->_dependencyInjector->getShared('dispatcher');
            if ($dispatcher->getRootNamespace() === null) {
                $dispatcher->setRootNamespace($this->getAppNamespace());
            }

            if ($this->_dependencyInjector->has('authorization')) {
                $dispatcher->attachEvent('dispatcher:beforeDispatch', function () use ($dispatcher) {
                    $dispatcher->getDependencyInjector()->getShared('authorization')->authorize($dispatcher);
                });
            }

            $controller = $dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);

            if ($controller === false) {
                return false;
            }

            $response = $this->_getResponse($dispatcher->getReturnedValue(), $moduleName,
                $dispatcher->getControllerName(), $dispatcher->getActionName());

            return $response;
        }

        /**
         * @param mixed  $actionReturnValue
         * @param        $module
         * @param string $controller
         * @param string $action
         *
         * @return \ManaPHP\Http\ResponseInterface
         * @throws \ManaPHP\Mvc\Application\Exception|\ManaPHP\Di\Exception
         */
        protected function _getResponse($actionReturnValue, $module, $controller, $action)
        {
            if ($actionReturnValue === false) {
                return $this->_dependencyInjector->getShared('response');
            } elseif ($actionReturnValue instanceof ResponseInterface) {
                return $actionReturnValue;
            } else {
                if ($actionReturnValue === null) {
                    $content = '';
                } elseif (is_string($actionReturnValue)) {
                    $content = $actionReturnValue;
                } else {
                    throw new Exception('the return value of Action is invalid: ' . $actionReturnValue);
                }

                $response = $this->_dependencyInjector->getShared('response');

                if ($this->_implicitView === true) {
                    $view = $this->_dependencyInjector->getShared('view');

                    $view->setContent($content);
                    $view->render($module, $controller, $action);
                    $response->setContent($view->getContent());
                } else {
                    $response->setContent($content);
                }

                return $response;
            }
        }
    }
}
