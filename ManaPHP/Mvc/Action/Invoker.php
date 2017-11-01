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
    protected $_reflectionParameters;

    /**
     * @param \ManaPHP\Mvc\ControllerInterface $controller
     * @param string                           $action
     * @param array                            $params
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Action\Exception
     */
    public function invokeAction($controller, $action, $params)
    {
        $actionMethod = $action . 'Action';

        $controllerName = get_class($controller);

        if (!isset($this->_reflectionParameters[$controllerName][$action])) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_reflectionParameters[$controllerName][$action] = (new \ReflectionMethod($controller, $actionMethod))->getParameters();
        }
        $parameters = $this->_reflectionParameters[$controllerName][$action];

        $args = [];
        $missing = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;
            $type = $parameter->getClass();

            if ($type !== null && is_subclass_of($type->getName(), Component::class)) {
                $value = $this->_dependencyInjector->get($type->getName());
            } elseif (isset($params[$name])) {
                $value = $params[$name];
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif ($this->request->hasJson($name)) {
                $value = $this->request->getJson($name);
            } elseif (count($params) === 1 && count($parameters) === 1) {
                $value = $params[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }

            if ($value === null) {
                $missing[] = $name;
                continue;
            }

            if ($parameter->isArray()) {
                $args[] = (array)$value;
            } else {
                $args[] = $value;
            }
        }

        if (count($missing) !== 0) {
            throw new ActionException('Missing required parameters: `:parameters`', ['parameters' => implode(',', $missing)]);
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
}