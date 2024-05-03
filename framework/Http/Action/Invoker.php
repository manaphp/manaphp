<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Mvc\Controller;
use ManaPHP\Mvc\ViewInterface;
use Psr\Container\ContainerInterface;
use function basename;
use function is_array;

class Invoker implements InvokerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected RequestInterface $request;

    protected function invokeMvc($object, string $action): mixed
    {
        $view = $this->container->get(ViewInterface::class);

        if ($this->request->method() === 'GET' && !$this->request->isAjax()) {
            $method = basename($action, 'Action') . 'View';
            if (method_exists($object, $method)) {
                $arguments = $this->argumentsResolver->resolve($object, $method);
                if (is_array($r = $object->$method(...$arguments))) {
                    return $view->setVars($r);
                } elseif ($r === null) {
                    return $view;
                } else {
                    return $r;
                }
            } elseif ($view->exists()) {
                return $view;
            }
        }

        $method = $action;
        $arguments = $this->argumentsResolver->resolve($object, $method);

        return $object->$method(...$arguments);
    }

    protected function invokeRest(object $object, string $action)
    {
        $method = $action;
        $arguments = $this->argumentsResolver->resolve($object, $method);

        return $object->$method(...$arguments);
    }

    public function invoke(object $object, string $action): mixed
    {
        if ($object instanceof Controller) {
            return $this->invokeMvc($object, $action);
        } else {
            return $this->invokeRest($object, $action);
        }
    }
}