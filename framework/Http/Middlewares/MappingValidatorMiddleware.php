<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Router\Attribute\MappingInterface;
use ManaPHP\Http\Router\MethodNotAllowedException;
use ManaPHP\Http\Server\Event\RequestValidating;
use ReflectionAttribute;
use ReflectionMethod;

class MappingValidatorMiddleware
{
    #[Autowired] protected RequestInterface $request;

    public function onValidating(#[Event] RequestValidating $event): void
    {
        $controller = $event->controller;
        $action = $event->action;
        $rm = new ReflectionMethod($controller, $action);

        if (($attributes = $rm->getAttributes(MappingInterface::class, ReflectionAttribute::IS_INSTANCEOF)) !== []) {
            $allowed = false;

            $method = $this->request->method();
            foreach ($attributes as $attribute) {
                /** @var MappingInterface $mapping */
                $mapping = $attribute->newInstance();
                if ($mapping->getMethod() === $method) {
                    $allowed = true;
                }
            }

            if (!$allowed) {
                throw new MethodNotAllowedException("`$method` method is not allowed");
            }
        }
    }
}