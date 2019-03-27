<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method mixed beforeInvoke(string $action);
 * @method mixed afterInvoke(string $action, mixed $r);
 *
 * @property-read \ManaPHP\ViewInterface                  $view
 * @property-read \ManaPHP\View\FlashInterface            $flash
 * @property-read \ManaPHP\View\FlashInterface            $flashSession
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\Http\CookiesInterface          $cookies
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface          $session
 * @property-read \ManaPHP\Paginator                      $paginator
 * @property-read \ManaPHP\CacheInterface                 $viewsCache
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Security\SecintInterface       $secint
 * @property-read \ManaPHP\Http\FilterInterface           $filter
 * @property-read \ManaPHP\Db\Model\MetadataInterface     $modelsMetadata
 * @property-read \ManaPHP\UrlInterface                   $url
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                $router
 */
abstract class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @return array
     */
    public function getAcl()
    {
        return [];
    }
}