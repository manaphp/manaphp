<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\ValidatingFilterInterface;
use ManaPHP\Mvc\Controller;

/**
 * @property-read \ManaPHP\Mvc\ViewInterface     $view
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class VerbsFilter extends Filter implements ValidatingFilterInterface
{
    public function onValidating(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Http\Controller $controller */
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        if (!$verbs = $controller->getVerbs()[$action] ?? false) {
            return;
        }

        $request_method = $this->request->getMethod();

        if (is_string($verbs) ? $request_method === $verbs : in_array($request_method, $verbs, true)) {
            return;
        }

        if ($request_method === 'GET'
            && $controller instanceof Controller
            && !$this->request->isAjax()
            && $this->view->exists()
        ) {
            return;
        }

        throw new MethodNotAllowedHttpException(is_string($verbs) ? [$verbs] : $verbs);
    }
}