<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Action\InvokerInterface;
use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;
use ManaPHP\Http\Server\Event\RequestAuthorized;
use ManaPHP\Http\Server\Event\RequestAuthorizing;
use ManaPHP\Http\Server\Event\RequestInvoked;
use ManaPHP\Http\Server\Event\RequestInvoking;
use ManaPHP\Http\Server\Event\RequestReady;
use ManaPHP\Http\Server\Event\RequestValidated;
use ManaPHP\Http\Server\Event\RequestValidating;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function explode;
use function is_string;
use function preg_match;

class Dispatcher implements DispatcherInterface
{
    use ContextTrait;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected InvokerInterface $invoker;

    public function getArea(): ?string
    {
        if (preg_match('#\\\\Areas\\\\(\w+)\\\\Controllers\\\\#', $this->getController() ?? '', $match) === 1) {
            return $match[1];
        } else {
            return null;
        }
    }

    public function getController(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->controller;
    }

    public function getAction(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->action;
    }

    public function getParams(): array
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->params;
    }

    public function getParam(int|string $name, mixed $default = null): mixed
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        $params = $context->params;
        return $params[$name] ?? $default;
    }

    public function hasParam(string $name): bool
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return isset($context->params[$name]);
    }

    public function getHandler(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->handler;
    }

    public function invokeAction(object $controller, string $action): mixed
    {
        if (!method_exists($controller, $action)) {
            throw new NotFoundActionException(['`{1}::{2}` method does not exist', $controller::class, $action]);
        }

        $this->eventDispatcher->dispatch(new RequestAuthorizing($this, $controller, $action));
        $this->eventDispatcher->dispatch(new RequestAuthorized($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestValidating($this, $controller, $action));
        $this->eventDispatcher->dispatch(new RequestValidated($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestReady($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestInvoking($this, $controller, $action));

        try {
            /** @var DispatcherContext $context */
            $context = $this->getContext();

            $context->isInvoking = true;
            $return = $this->invoker->invoke($controller, $action);
        } finally {
            $context->isInvoking = false;
        }

        $this->eventDispatcher->dispatch(new RequestInvoked($this, $controller, $action, $return));

        return $return;
    }

    public function dispatch(string $handler, array $params): mixed
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        $context->handler = $handler;
        $context->params = $params;

        $globals = $this->request->getContext();

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $globals->_REQUEST[$k] = $v;
            }
        }
        list($controller, $action) = explode('::', $handler);

        $context->controller = $controller;
        $context->action = $action;

        if (!class_exists($controller)) {
            throw new NotFoundControllerException(['`{1}` class cannot be loaded', $controller]);
        }

        $controllerInstance = $this->container->get($controller);
        $context->controllerInstance = $controllerInstance;

        return $this->invokeAction($controllerInstance, $action);
    }

    public function getControllerInstance(): ?object
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->controllerInstance;
    }

    public function isInvoking(): bool
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->isInvoking;
    }
}
