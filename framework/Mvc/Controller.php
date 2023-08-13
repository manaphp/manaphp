<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Mvc\View\FlashInterface;

class Controller extends \ManaPHP\Http\Controller
{
    #[Inject] protected ViewInterface $view;
    #[Inject] protected FlashInterface $flash;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected SessionInterface $session;
    #[Inject] protected AuthorizationInterface $authorization;

    public function invoke(string $action): mixed
    {
        if ($this->request->isGet() && !$this->request->isAjax()) {
            $method = $action . 'View';
            if (method_exists($this, $method)) {
                $arguments = $this->argumentsResolver->resolve($this, $method);
                if (is_array($r = $this->$method(...$arguments))) {
                    return $this->view->setVars($r);
                } elseif ($r === null) {
                    return $this->view;
                } else {
                    return $r;
                }
            } elseif ($this->view->exists()) {
                return $this->view;
            }
        }

        $method = $action . 'Action';
        $arguments = $this->argumentsResolver->resolve($this, $method);

        return $this->$method(...$arguments);
    }
}