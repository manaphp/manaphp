<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Mvc\Controller;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Mvc\ViewInterface     $view
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class VerbsPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        }

        if ($this->enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate']);
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     * @throws MethodNotAllowedHttpException
     */
    public function onRequestValidate(EventArgs $eventArgs)
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
            && Reflection::isInstanceOf($controller, Controller::class)
            && !$this->request->isAjax()
            && $this->view->exists()
        ) {
            return;
        }

        throw new MethodNotAllowedHttpException(is_string($verbs) ? [$verbs] : $verbs);
    }
}