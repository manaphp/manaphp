<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Filters\CsrfFilter\AttackDetectedException;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\ValidatingFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Mvc\ViewInterface;
use ManaPHP\Rest\Controller as RestController;

class CsrfFilter extends Filter implements ValidatingFilterInterface
{
    #[Inject]
    protected RequestInterface $request;
    #[Inject]
    protected ViewInterface $view;

    protected bool $strict;
    protected array $domains;

    public function __construct(bool $strict = true, array $domains = [])
    {
        $this->strict = $strict;
        $this->domains = $domains;
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
        }

        throw new AttackDetectedException();
    }
}