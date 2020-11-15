<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Plugin;
use ManaPHP\Plugins\CsrfPlugin\AttackDetectedException;
use ManaPHP\Rest\Controller as RestController;

/**
 * Class CsrfPlugin
 *
 * @package ManaPHP\Http
 *
 * @property-read \ManaPHP\Mvc\ViewInterface $view
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
     * @var array
     */
    protected $_domains = [];

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

        if ($domains = $options['domains'] ?? false) {
            if (is_string($domains)) {
                $this->_domains = preg_split('#[\s,]+#', $domains, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $this->_domains = $domains;
            }
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

        if (($pos = strpos($origin, '://')) === false) {
            return false;
        }
        $origin_domain = substr($origin, $pos + 3);

        if ($origin_domain === $host) {
            return true;
        }

        if ($domains = $this->_domains) {
            if (in_array($origin_domain, $domains, true)) {
                return true;
            }

            foreach ($domains as $domain) {
                if ($domain[0] === '*') {
                    if (str_ends_with($origin_domain, substr($domain, 1))) {
                        return true;
                    }
                } elseif (str_contains($domain, '^') && str_contains($domain, '$')) {
                    if (preg_match($origin_domain, $domain) === 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function onRequestValidate(EventArgs $eventArgs)
    {
        if ($this->_isOriginSafe()) {
            return;
        }

        $controller = $eventArgs->data['controller'];

        if ($controller instanceof RestController) {
            return;
        }

        if ($this->request->isGet()) {
            if (!$this->_strict) {
                return;
            }

            if ($controller instanceof MvcController && !$this->request->isAjax() && $this->view->exists()) {
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