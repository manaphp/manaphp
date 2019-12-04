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

    public function getVerbs()
    {
        return [
            'index' => 'GET',
            'list' => 'GET',
            'detail' => 'GET',
            'create' => 'POST',
            'update' => 'POST',
            'edit' => 'POST',
            'save' => 'POST',
            'delete' => ['DELETE', 'POST'],
            'enable' => 'POST',
            'disable' => 'POST',
            'active' => 'POST',
            'inactive' => 'POST',
        ];
    }
}