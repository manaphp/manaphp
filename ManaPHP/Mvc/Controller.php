<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;

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
 * @property \ManaPHP\Mvc\ViewInterface                    $view
 * @property \ManaPHP\Mvc\View\FlashInterface              $flash
 * @property \ManaPHP\Mvc\View\FlashInterface              $flashSession
 * @property \ManaPHP\Security\CaptchaInterface            $captcha
 * @property \ManaPHP\Http\ClientInterface                 $httpClient
 * @property \ManaPHP\Authentication\PasswordInterface     $password
 * @property \ManaPHP\Http\CookiesInterface                $cookies
 * @property \ManaPHP\Mvc\Model\ManagerInterface           $modelsManager
 * @property \ManaPHP\CounterInterface                     $counter
 * @property \ManaPHP\CacheInterface                       $cache
 * @property \ManaPHP\DbInterface                          $db
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Http\RequestInterface                $request
 * @property \ManaPHP\Http\ResponseInterface               $response
 * @property \ManaPHP\Security\CryptInterface              $crypt
 * @property \ManaPHP\Http\Session\BagInterface            $persistent
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\LoggerInterface                      $logger
 * @property \Application\Configure                        $configure
 * @property \ManaPHP\Http\SessionInterface                $session
 * @property \ManaPHP\Security\CsrfTokenInterface          $csrfToken
 * @property \ManaPHP\Paginator                            $paginator
 * @property \ManaPHP\Cache\AdapterInterface               $viewsCache
 * @property \ManaPHP\FilesystemInterface                  $filesystem
 * @property \ManaPHP\Security\RandomInterface             $random
 * @property \ManaPHP\Message\QueueInterface               $messageQueue
 * @property \ManaPHP\Security\RateLimiterInterface        $rateLimiter
 * @property \ManaPHP\Meter\LinearInterface                $linearMeter
 * @property \ManaPHP\Meter\RoundInterface                 $roundMeter
 * @property \ManaPHP\Security\SecintInterface             $secint
 * @property \ManaPHP\Http\FilterInterface                 $filter
 * @property \ManaPHP\Mvc\Model\MetadataInterface          $modelsMetadata
 * @property \ManaPHP\Mvc\UrlInterface                     $url
 * @property \ManaPHP\StopwatchInterface                   $stopwatch
 * @property \ManaPHP\Security\HtmlPurifierInterface       $htmlPurifier
 * @property \ManaPHP\Cli\EnvironmentInterface             $environment
 * @property \ManaPHP\Net\ConnectivityInterface            $netConnectivity
 * @property \ManaPHP\Redis                                $redis
 * @property \MongoDB\Client                               $mongodb
 * @property \Elasticsearch\Client                         $elasticsearch
 * @property \ManaPHP\ZookeeperInterface                   $zookeeper
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * \ManaPHP\Mvc\Controller constructor
     *
     */
    final public function __construct()
    {
        if (method_exists($this, 'onConstruct')) {
            $this->{'onConstruct'}();
        }
    }
}