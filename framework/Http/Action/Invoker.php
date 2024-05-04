<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Mvc\Controller;
use ManaPHP\Mvc\View\Attribute\View;
use ManaPHP\Mvc\ViewInterface;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use function call_user_func;

class Invoker implements InvokerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected RequestInterface $request;

    protected function invokeMvc($object, string $action): mixed
    {
        if ($this->request->method() === 'GET' && !$this->request->isAjax()) {
            $view = $this->container->get(ViewInterface::class);

            $rMethod = new ReflectionMethod($object, $action);
            $attributes = $rMethod->getAttributes(View::class, \ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                /** @var View $viewAttribute */
                $viewAttribute = $attributes[0]->newInstance();
                if ($viewAttribute->getVars() !== null) {
                    $view->setVars(call_user_func([$object, $viewAttribute->getVars()]));
                }
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