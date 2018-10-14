<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method bool beforeExecuteRoute();
 * @method bool afterExecuteRoute();
 *
 * @property-read \ManaPHP\ViewInterface                    $view
 * @property-read \ManaPHP\View\FlashInterface              $flash
 * @property-read \ManaPHP\View\FlashInterface              $flashSession
 * @property-read \ManaPHP\Security\CaptchaInterface        $captcha
 * @property-read \ManaPHP\Authentication\PasswordInterface $password
 * @property-read \ManaPHP\Http\CookiesInterface            $cookies
 * @property-read \ManaPHP\CounterInterface                 $counter
 * @property-read \ManaPHP\Http\RequestInterface            $request
 * @property-read \ManaPHP\Http\ResponseInterface           $response
 * @property-read \ManaPHP\Mvc\DispatcherInterface          $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface            $session
 * @property-read \ManaPHP\Security\CsrfTokenInterface      $csrfToken
 * @property-read \ManaPHP\Paginator                        $paginator
 * @property-read \ManaPHP\Cache\EngineInterface            $viewsCache
 * @property-read \ManaPHP\Message\QueueInterface           $messageQueue
 * @property-read \ManaPHP\Security\RateLimiterInterface    $rateLimiter
 * @property-read \ManaPHP\Meter\LinearInterface            $linearMeter
 * @property-read \ManaPHP\Meter\RoundInterface             $roundMeter
 * @property-read \ManaPHP\Security\SecintInterface         $secint
 * @property-read \ManaPHP\Http\FilterInterface             $filter
 * @property-read \ManaPHP\Db\Model\MetadataInterface       $modelsMetadata
 * @property-read \ManaPHP\UrlInterface                     $url
 * @property-read \ManaPHP\StopwatchInterface               $stopwatch
 * @property-read \ManaPHP\Security\HtmlPurifierInterface   $htmlPurifier
 * @property-read \ManaPHP\Net\ConnectivityInterface        $netConnectivity
 * @property-read \ManaPHP\RouterInterface                  $router
 */
abstract class Controller extends Component implements ControllerInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Controller');
    }
}