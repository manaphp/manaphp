<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method void initialize();
 * @method bool beforeExecuteRoute();
 * @method bool afterExecuteRoute();
 * @method void onConstruct();
 *
 *
 * @property \ManaPHP\ViewInterface                    $view
 * @property \ManaPHP\View\FlashInterface              $flash
 * @property \ManaPHP\View\FlashInterface              $flashSession
 * @property \ManaPHP\Security\CaptchaInterface        $captcha
 * @property \ManaPHP\Authentication\PasswordInterface $password
 * @property \ManaPHP\Http\CookiesInterface            $cookies
 * @property \ManaPHP\CounterInterface                 $counter
 * @property \ManaPHP\Http\RequestInterface            $request
 * @property \ManaPHP\Http\ResponseInterface           $response
 * @property \ManaPHP\Mvc\DispatcherInterface          $dispatcher
 * @property \ManaPHP\Http\SessionInterface            $session
 * @property \ManaPHP\Security\CsrfTokenInterface      $csrfToken
 * @property \ManaPHP\Paginator                        $paginator
 * @property \ManaPHP\Cache\EngineInterface            $viewsCache
 * @property \ManaPHP\Message\QueueInterface           $messageQueue
 * @property \ManaPHP\Security\RateLimiterInterface    $rateLimiter
 * @property \ManaPHP\Meter\LinearInterface            $linearMeter
 * @property \ManaPHP\Meter\RoundInterface             $roundMeter
 * @property \ManaPHP\Security\SecintInterface         $secint
 * @property \ManaPHP\Http\FilterInterface             $filter
 * @property \ManaPHP\Db\Model\MetadataInterface       $modelsMetadata
 * @property \ManaPHP\View\UrlInterface                $url
 * @property \ManaPHP\StopwatchInterface               $stopwatch
 * @property \ManaPHP\Security\HtmlPurifierInterface   $htmlPurifier
 * @property \ManaPHP\Net\ConnectivityInterface        $netConnectivity
 * @property \ManaPHP\RouterInterface                  $router
 */
abstract class Controller extends Component implements ControllerInterface, LogCategorizable
{
    public function categorizeLog()
    {
        $className = get_called_class();
        $controller = basename($className, 'Controller');
        if (strpos($className, '\Areas\\') && preg_match('#/Areas/(\w+)/Controllers#', strtr($className, '\\', '/'), $match)) {
            return "$match[1].$controller";
        } else {
            return $controller;
        }
    }
}