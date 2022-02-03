<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

/**
 * @property-read \ManaPHP\Mvc\ViewInterface           $view
 * @property-read \ManaPHP\Mvc\View\FlashInterface     $flash
 * @property-read \ManaPHP\Http\CookiesInterface       $cookies
 * @property-read \ManaPHP\Http\SessionInterface       $session
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class Controller extends \ManaPHP\Http\Controller
{
    public function getAcl(): array
    {
        return ['*' => '@index'];
    }

    public function invoke(string $action): mixed
    {
        if ($this->request->isGet() && !$this->request->isAjax()) {
            $view = $action . 'View';
            if (method_exists($this, $view)) {
                if (is_array($r = $this->invoker->invoke($this, $view))) {
                    return $this->view->setVars($r);
                } elseif ($r === null) {
                    return $this->view;
                } else {
                    return $r;
                }
            } elseif ($this->view->exists()) {
                return $this->view;
            } else {
                return $this->invoker->invoke($this, $action . 'Action');
            }
        } else {
            return $this->invoker->invoke($this, $action . 'Action');
        }
    }
}