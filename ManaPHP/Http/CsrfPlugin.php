<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Http\CsrfPlugin\AttackDetectedException;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Plugin;
use ManaPHP\Rest\Controller as RestController;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Mvc\ViewInterface     $view
 */
class CsrfPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var bool
     */
    protected $strict = true;

    /**
     * @var array
     */
    protected $domains = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        }

        if (isset($options['strict'])) {
            $this->strict = (bool)$options['strict'];
        }

        if ($domains = $options['domains'] ?? false) {
            if (is_string($domains)) {
                $this->domains = preg_split('#[\s,]+#', $domains, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $this->domains = $domains;
            }
        }

        if ($this->enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate']);
        }
    }

    /**
     * @return bool
     */
    protected function isOriginSafe()
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

        if ($domains = $this->domains) {
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

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     * @throws AttackDetectedException
     */
    public function onRequestValidate(EventArgs $eventArgs)
    {
        if ($this->isOriginSafe()) {
            return;
        }

        $controller = $eventArgs->data['controller'];

        if ($controller instanceof RestController) {
            return;
        }

        if ($this->request->isGet()) {
            if (!$this->strict) {
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