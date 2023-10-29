<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestValidating;
use ReflectionMethod;

class VerbsMiddleware
{
    #[Autowired] protected RequestInterface $request;

    public function onValidating(#[Event] RequestValidating $event): void
    {
        $controller = $event->controller;
        $action = $event->action;

        $rm = new ReflectionMethod($controller, $action . 'Action');
        if (($attribute = $rm->getAttributes(AcceptVerbs::class)[0] ?? null) !== null) {
            $request_method = $this->request->getMethod();
            $acceptVerbs = $attribute->newInstance();
            if (!\in_array($request_method, $acceptVerbs->verbs, true)) {
                throw new MethodNotAllowedHttpException($acceptVerbs->verbs);
            }
        }
    }
}