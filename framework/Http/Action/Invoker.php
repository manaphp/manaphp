<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ArgumentsResolverInterface;
use ManaPHP\Mvc\Controller;
use ManaPHP\Mvc\ViewInterface;
use Psr\Container\ContainerInterface;

class Invoker implements InvokerInterface
{
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected MakerInterface $maker;
    #[Inject] protected ArgumentsResolverInterface $argumentsResolver;
    #[Inject] protected RequestInterface $request;

    #[Value] protected array $resolvers = ['model', 'identity', 'session', 'request'];

    protected ArgumentsResolverInterface $argumentResolver;

    public function __construct()
    {
        $this->argumentResolver = $this->maker->make(
            ArgumentsResolverInterface::class, ['resolvers' => $this->resolvers]
        );
    }

    protected function invokeMvc($object, string $action): mixed
    {
        $view = $this->container->get(ViewInterface::class);

        if ($this->request->isGet() && !$this->request->isAjax()) {
            $method = $action . 'View';
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

        $method = $action . 'Action';
        $arguments = $this->argumentsResolver->resolve($object, $method);

        return $object->$method(...$arguments);
    }

    protected function invokeRest(object $object, string $action)
    {
        $method = $action . 'Action';
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