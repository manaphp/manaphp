<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Event\EventArgs;
use ManaPHP\Http\Middleware;
use ManaPHP\Http\Middlewares\CsrfMiddleware\AttackDetectedException;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Rest\Controller as RestController;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Mvc\ViewInterface     $view
 */
class CsrfMiddleware extends Middleware
{
    protected bool $strict = true;
    protected array $domains = [];

    public function __construct(array $options = [])
    {
        parent::__construct($options);

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
    }

    protected function isOriginSafe(): bool
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

    public function onValidating(EventArgs $eventArgs): void
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

            if ($controller instanceof MvcController
                && !$this->request->isAjax()
                && $this->view->exists()
            ) {
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