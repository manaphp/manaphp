<?php
namespace ManaPHP;

use ManaPHP\ActionInvoker\Exception as ActionException;
use ManaPHP\ActionInvoker\NotFoundException;

/**
 * Class ManaPHP\ActionInvoker
 *
 * @package ManaPHP\Mvc\Action
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class ActionInvoker extends Component implements ActionInvokerInterface
{
    /**
     * @var \ManaPHP\Controller
     */
    protected $_controller;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @return \ManaPHP\Controller
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     * @param array               $params
     *
     * @return array
     * @throws \ManaPHP\ActionInvoker\Exception
     */
    protected function _buildArgs($controller, $action, $params)
    {
        $args = [];
        $missing = [];

        $di = $this->_di;

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $parameters = (new \ReflectionMethod($controller, $action . 'Action'))->getParameters();
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getClass();
            if ($type !== null) {
                $type = $type->getName();
            } elseif ($parameter->isDefaultValueAvailable()) {
                $type = gettype($parameter->getDefaultValue());
            }

            if ($className = ($c = $parameter->getClass()) ? $c->getName() : null) {
                if ($di->has($name)) {
                    $value = $di->get($name);
                } elseif ($di->has($className)) {
                    $value = $di->get($className);
                } else {
                    $value = $di->getShared($className);
                }
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

        return $args;
    }

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     * @param array               $params
     *
     * @return mixed
     * @throws \ManaPHP\ActionInvoker\Exception
     * @throws \ManaPHP\ActionInvoker\NotFoundException
     */
    public function invoke($controller, $action, $params)
    {
        $this->_controller = $controller;
        $this->_action = $action;

        $actionMethod = $action . 'Action';

        if (!method_exists($controller, $actionMethod)) {
            throw new NotFoundException([
                '`:controller:::action` method does not exist',
                'action' => $actionMethod,
                'controller' => get_class($controller)
            ]);
        }

        if (method_exists($controller, 'beforeInvoke') && ($r = $controller->beforeInvoke($action)) !== null) {
            return $r;
        }

        if (($r = $this->fireEvent('actionInvoker:beforeInvoke', $action)) !== null) {
            return $r;
        }

        $args = $this->_buildArgs($controller, $action, $params);

        switch (count($args)) {
            case 0:
                $r = $controller->$actionMethod();
                break;
            case 1:
                $r = $controller->$actionMethod($args[0]);
                break;
            default:
                $r = call_user_func_array([$controller, $actionMethod], $args);
                break;
        }

        $this->fireEvent('actionInvoker:afterInvoke', ['action' => $action, 'return' => $r]);

        if (method_exists($controller, 'afterInvoke')) {
            $controller->afterInvoke($action, $r);
        }

        return $r;
    }
}