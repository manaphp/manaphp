<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * Class Handler
 *
 * @package ManaPHP\Cli
 *
 * @property-read \ManaPHP\Cli\ConsoleInterface $console
 * @property-read \ManaPHP\Cli\RequestInterface $request
 */
class Handler extends Component implements HandlerInterface
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessController($keyword)
    {
        $controllers = [];

        foreach (LocalFS::glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                $controllers[$matches[1]] = "ManaPHP\\Cli\Controllers\\{$matches[1]}Controller";
            }
        }

        if ($this->alias->has('@cli')) {
            foreach (LocalFS::glob('@cli/*Controller.php') as $file) {
                if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                    $controllers[$matches[1]] = $this->alias->resolveNS("@ns.cli\\{$matches[1]}Controller");
                }
            }
        }

        $guessed = [];
        foreach ($controllers as $name => $className) {
            if (stripos($name, $keyword) === 0) {
                $guessed[] = $className;
            }
        }

        if (!$guessed) {
            foreach ($controllers as $name => $className) {
                if (stripos($name, $keyword) !== false) {
                    $guessed[] = $className;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param string $controllerName
     *
     * @return array
     */
    protected function _getActions($controllerName)
    {
        $actions = [];

        foreach (get_class_methods($controllerName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) === 1 && $match[1] !== 'help') {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }

    /**
     * @param string $controllerName
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessAction($controllerName, $keyword)
    {
        $actions = $this->_getActions($controllerName);

        $guessed = [];
        foreach ($actions as $action) {
            if (stripos($action, $keyword) === 0) {
                $guessed[] = $action;
            }
        }

        if (!$guessed) {
            foreach ($actions as $action) {
                if (stripos($action, $keyword) !== false) {
                    $guessed[] = $action;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->_args = $args ?? $GLOBALS['argv'];

        list(, $controllerName, $actionName) = array_pad($this->_args, 3, null);

        if ($actionName === null) {
            $this->request->parse([]);
        } elseif ($actionName[0] === '-') {
            $this->request->parse(array_splice($this->_args, 2));
        } else {
            $this->request->parse(array_splice($this->_args, 3));
        }

        if ($actionName !== null && $actionName[0] === '-') {
            $actionName = null;
        }

        if ($controllerName === 'list') {
            $controllerName = 'help';
            $actionName = $actionName ?: 'list';
        } elseif ($controllerName === null) {
            $controllerName = 'help';
        } elseif ($controllerName === '--help' || $controllerName === '-h') {
            $controllerName = 'help';
            $actionName = 'list';
        } elseif ($controllerName === 'help' && $actionName !== null && $actionName !== 'list') {
            $controllerName = $actionName;
            $actionName = 'help';
        }

        $controllerName = Str::camelize($controllerName);
        $actionName = lcfirst(Str::camelize($actionName));

        $controller = null;

        if ($this->alias->has('@ns.cli')) {
            $namespaces = ['@ns.cli', 'ManaPHP\\Cli\\Controllers'];
        } else {
            $namespaces = ['ManaPHP\\Cli\\Controllers'];
        }

        foreach ($namespaces as $prefix) {
            $className = $this->alias->resolveNS($prefix . '\\' . $controllerName . 'Controller');

            if (class_exists($className)) {
                $controller = $className;
                break;
            }
        }

        if (!$controller) {
            $guessed = $this->_guessController($controllerName);
            if ($guessed) {
                $controller = $guessed;
                $controllerName = basename(substr($controller, strrpos($controller, '\\')), 'Controller');
            } else {
                $action = lcfirst($controllerName) . ':' . $actionName;
                return $this->console->error(['`:action` action is not exists', 'action' => $action]);
            }
        }

        /** @var \ManaPHP\Controller $instance */
        $instance = $this->getShared($controller);
        if ($actionName === '') {
            $actions = $this->_getActions($controller);
            if (count($actions) === 1) {
                $actionName = $actions[0];
            } elseif (in_array('default', $actions, true)) {
                $actionName = 'default';
            } else {
                $actionName = 'help';
            }
        }

        if ($actionName !== 'help' && $this->request->has('help')) {
            $actionName = 'help';
        }

        if (!$instance->isInvokable($actionName)) {
            $guessed = $this->_guessAction($controller, $actionName);
            if (!$guessed) {
                $action = lcfirst($controllerName) . ':' . $actionName;
                return $this->console->error(['`:action` sub action is not exists', 'action' => $action]);
            } else {
                $actionName = $guessed;
            }
        }

        $actionMethod = $actionName . 'Action';
        $this->request->completeShortNames($instance, $actionMethod);
        $r = $instance->invoke($actionName);

        return is_int($r) ? $r : 0;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->_args;
    }
}