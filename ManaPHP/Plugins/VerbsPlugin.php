<?php

namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Mvc\Controller;
use ManaPHP\Plugin;

/**
 * Class VerbsPlugin
 *
 * @package ManaPHP\Plugins
 *
 * @property-read \ManaPHP\ViewInterface $view
 */
class VerbsPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * VerbsPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        if ($this->_enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate']);
        }
    }

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
            && $controller instanceof Controller
            && !$this->request->isAjax()
            && $this->view->exists()) {
            return;
        }

        throw new MethodNotAllowedHttpException(is_string($verbs) ? [$verbs] : $verbs);
    }
}