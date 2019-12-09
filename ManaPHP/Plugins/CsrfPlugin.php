<?php
namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Mvc\Controller;
use ManaPHP\Plugin;
use ManaPHP\Plugins\CsrfPlugin\AttackDetectedException;

/**
 * Class CsrfPlugin
 * @package ManaPHP\Plugins
 *
 * @property-read \ManaPHP\ViewInterface $view
 */
class CsrfPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var bool
     */
    protected $_strict = true;

    /**
     * CsrfPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        if (isset($options['strict'])) {
            $this->_strict = (bool)$options['strict'];
        }

        if ($this->_enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate']);
        }
    }

    /**
     * @return bool
     */
    protected function _isOriginSafe()
    {
        if (($origin = $this->request->getOrigin(false)) === '') {
            return false;
        }

        if (($host = $this->request->getHost()) === '') {
            return false;
        }

        if (($pos = strpos($origin, '://')) > 0 && substr($origin, $pos + 3) === $host) {
            return true;
        }

        return false;
    }

    public function onRequestValidate(EventArgs $eventArgs)
    {
        if ($this->_isOriginSafe()) {
            return;
        } elseif ($this->request->isGet()) {
            if (!$this->_strict) {
                return;
            }
            $controller = $eventArgs->data['controller'];
            if ($controller instanceof Controller && !$this->request->isAjax() && $this->view->exists()) {
                return;
            }

            $action = $eventArgs->data['action'];
            $verbs = $controller->getVerbs()[$action] ?? [];
            if (is_string($verbs) ? 'GET' === $verbs : in_array('GET', $verbs, true)) {
                return;
            }
        }

        throw new AttackDetectedException();
    }
}