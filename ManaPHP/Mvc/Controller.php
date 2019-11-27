<?php

namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method mixed beforeInvoke(string $action);
 * @method mixed afterInvoke(string $action, mixed $r);
 *
 * @property-read \ManaPHP\ViewInterface                     $view
 * @property-read \ManaPHP\View\FlashInterface               $flash
 * @property-read \ManaPHP\View\FlashInterface               $flashSession
 * @property-read \ManaPHP\Security\CaptchaInterface         $captcha
 * @property-read \ManaPHP\Http\CookiesInterface             $cookies
 * @property-read \ManaPHP\Http\RequestInterface             $request
 * @property-read \ManaPHP\Http\ResponseInterface            $response
 * @property-read \ManaPHP\DispatcherInterface               $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface             $session
 * @property-read \ManaPHP\CacheInterface                    $viewsCache
 * @property-read \ManaPHP\Message\QueueInterface            $messageQueue
 * @property-read \ManaPHP\Db\Model\MetadataInterface        $modelsMetadata
 * @property-read \ManaPHP\UrlInterface                      $url
 * @property-read \ManaPHP\Security\HtmlPurifierInterface    $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                   $router
 * @property-read \ManaPHP\AuthorizationInterface            $authorization
 * @property-read \ManaPHP\Authorization\AclBuilderInterface $aclBuilder
 */
abstract class Controller extends \ManaPHP\Controller
{
    /**
     * @return array
     */
    public function getAcl()
    {
        return ['*' => '@index'];
    }
}