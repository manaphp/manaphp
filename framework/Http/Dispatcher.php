<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\Str;
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

class Dispatcher implements DispatcherInterface
{
    use ContextTrait;

    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected GlobalsInterface $globals;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected InvokerInterface $invoker;

    public function getArea(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->area;
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

    public function getPath(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        if ($context->path === null) {
            $area = $context->area;
            $controller = $context->controller;
            $action = $context->action;

            if ($controller === null || $action === null) {
                return null;
            }

            if ($action === 'index') {
                $path = $controller === 'Index' ? '/' : '/' . Str::snakelize($controller);
            } else {
                $path = '/' . Str::snakelize($controller) . '/' . Str::snakelize($action);
            }

            if ($area !== '' && $area !== null) {
                if ($area === 'Index' && $path === '/') {
                    null;
                } else {
                    $path = '/' . Str::snakelize($area) . $path;
                }
            }

            $context->path = $path;
        }

        return $context->path;
    }

    public function invokeAction(Controller $controller, string $action): mixed
    {
        $method = $action . 'Action';

        if (!method_exists($controller, $method)) {
            throw new NotFoundActionException(['`{1}::{2}` method does not exist', $controller::class, $method]);
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

    public function dispatch(?string $area, string $controller, string $action, array $params): mixed
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        $globals = $this->globals->get();

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $globals->_REQUEST[$k] = $v;
            }
        }

        if (($id = $params[0] ?? null) !== null && (is_int($id) || is_string($id))) {
            $globals->_REQUEST['id'] = $id;
        }

        if ($area) {
            $area = str_contains($area, '_') ? Str::pascalize($area) : ucfirst($area);
            $context->area = $area;
        }

        $controller = str_contains($controller, '_') ? Str::pascalize($controller) : ucfirst($controller);
        $context->controller = $controller;

        $action = str_contains($action, '_') ? Str::camelize($action) : $action;
        $context->action = $action;

        $context->params = $params;

        if ($area) {
            $controllerClassName = "App\\Areas\\$area\\Controllers\\{$controller}Controller";
        } else {
            $controllerClassName = "App\\Controllers\\{$controller}Controller";
        }

        if (!class_exists($controllerClassName)) {
            throw new NotFoundControllerException(['`{1}` class cannot be loaded', $controllerClassName]);
        }

        $controllerInstance = $this->container->get($controllerClassName);
        $context->controllerInstance = $controllerInstance;

        return $this->invokeAction($controllerInstance, $action);
    }

    public function getControllerInstance(): ?Controller
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
