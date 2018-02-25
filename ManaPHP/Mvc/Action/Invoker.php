<?php
namespace ManaPHP\Mvc\Action;

use ManaPHP\Component;
use ManaPHP\Mvc\Action\Exception as ActionException;

/**
 * Class ManaPHP\Mvc\Action\Invoker
 *
 * @package ManaPHP\Mvc\Action
 *
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Invoker extends Component implements InvokerInterface
{
    /**
     * @var \ReflectionParameter[][][]
     */
    protected $_actionParameters;

    /**
     * @var [][]
     */
    protected $_actions = [];

    /**
     * @param \ManaPHP\Mvc\ControllerInterface $controller
     *
     * @return array
     */
    protected function _getActions($controller)
    {
        $controllerName = get_class($controller);

        if (!isset($this->_actions[$controllerName])) {
            $this->_actions[$controllerName] = [];

            foreach (get_class_methods($controller) as $method) {
                if ($method[0] !== '_' && substr_compare($method, 'Action', -6) === 0) {
                    $this->_actions[$controllerName][] = substr($method, 0, -6);
                }
            }
        }

        return $this->_actions[$controllerName];
    }

    /**
     * @param \ManaPHP\Mvc\ControllerInterface $controller
     * @param string                           $action
     * @param array                            $params
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Action\Exception
     */
    protected function _invokeAction($controller, $action, $params)
    {
        $actionMethod = $action . 'Action';

        $controllerName = get_class($controller);

        if (!isset($this->_actionParameters[$controllerName][$action])) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->_actionParameters[$controllerName][$action] = (new \ReflectionMethod($controller, $actionMethod))->getParameters();
        }
        $parameters = $this->_actionParameters[$controllerName][$action];

        $args = [];
        $missing = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getClass();
            if ($type !== null) {
                $type = $type->getName();
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $type = gettype($parameter->getDefaultValue());
                }
            }

            if ($type !== null && is_subclass_of($type, Component::class)) {
                $value = $this->_dependencyInjector->get($type->getName());
            } elseif (isset($params[$name])) {
                $value = $params[$name];
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif (count($params) === 1 && count($parameters) === 1) {
                $value = $params[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }

            if ($value === null) {
                $missing[] = $name;
                continue;
            }

            switch ($type) {
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'double':
                    $value = (float)$value;
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
                case 'array':
                    $value = (array)$value;
                    break;
            }

            if ($parameter->isArray()) {
                $args[] = (array)$value;
            } else {
                $args[] = $value;
            }
        }

        if (count($missing) !== 0) {
            throw new ActionException(['Missing required parameters: `:parameters`', 'parameters' => implode(',', $missing)]);
        }

        switch (count($args)) {
            case 0:
                return $controller->$actionMethod();
            case 1:
                return $controller->$actionMethod($args[0]);
            case 2:
                return $controller->$actionMethod($args[0], $args[1]);
            case 3:
                return $controller->$actionMethod($args[0], $args[1], $args[2]);
            default:
                return call_user_func_array([$controller, $actionMethod], $args);
        }
    }

    /**
     * @param \ManaPHP\Mvc\ControllerInterface $controller
     * @param string                           $action
     * @param array                            $params
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Action\Exception
     * @throws \ManaPHP\Mvc\Action\NotFoundException
     */
    public function invokeAction($controller, $action, $params)
    {
        $actions = $this->_getActions($controller);

        if (!in_array($action, $actions, true)) {
            throw new NotFoundException(['`:controller:::action` is not found, action is case sensitive.'/**m061a35fc1c0cd0b6f*/,
                'action' => $action . 'Action', 'controller' => get_class($controller)]);
        }

        return $this->_invokeAction($controller, $action, $params);
    }
}