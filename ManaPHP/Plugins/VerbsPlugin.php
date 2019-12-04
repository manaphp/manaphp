<?php
namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MethodNotAllowedHttpException;
use ManaPHP\Plugin;

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

        if (is_string($verbs)) {
            if ($request_method === $verbs) {
                return;
            }
            throw new MethodNotAllowedHttpException([$verbs]);
        } else {
            if (in_array($request_method, $verbs, true)) {
                return;
            }
            throw new MethodNotAllowedHttpException($verbs);
        }
    }
}