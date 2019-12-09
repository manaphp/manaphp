<?php

namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @property-read \ManaPHP\ViewInterface                     $view
 * @property-read \ManaPHP\View\FlashInterface               $flash
 * @property-read \ManaPHP\View\FlashInterface               $flashSession
 * @property-read \ManaPHP\Http\CookiesInterface             $cookies
 * @property-read \ManaPHP\Http\SessionInterface             $session
 * @property-read \ManaPHP\CacheInterface                    $viewsCache
 * @property-read \ManaPHP\UrlInterface                      $url
 * @property-read \ManaPHP\AuthorizationInterface            $authorization
 * @property-read \ManaPHP\Authorization\AclBuilderInterface $aclBuilder
 */
abstract class Controller extends \ManaPHP\Http\Controller
{
    /**
     * @return array
     */
    public function getAcl()
    {
        return ['*' => '@index'];
    }

    public function invoke($action)
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