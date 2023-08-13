<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\ValidatingFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Mvc\ViewInterface;
use ReflectionMethod;

class VerbsFilter extends Filter implements ValidatingFilterInterface
{
    #[Inject]
    protected ViewInterface $view;
    #[Inject]
    protected RequestInterface $request;

    public function onValidating(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Http\Controller $controller */
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $rm = new ReflectionMethod($controller, $action . 'Action');
        if (($attribute = $rm->getAttributes(AcceptVerbs::class)[0] ?? null) !== null) {
            $request_method = $this->request->getMethod();
            $acceptVerbs = $attribute->newInstance();
            if (!in_array($request_method, $acceptVerbs->verbs, true)) {
                throw new MethodNotAllowedHttpException($acceptVerbs->verbs);
            }
        }
    }
}