<?php

namespace ManaPHP\Rest;

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
 * @property \ManaPHP\Security\CaptchaInterface        $captcha
 * @property \ManaPHP\Authentication\PasswordInterface $password
 * @property \ManaPHP\CounterInterface                 $counter
 * @property \ManaPHP\Http\RequestInterface            $request
 * @property \ManaPHP\Http\ResponseInterface           $response
 * @property \ManaPHP\Mvc\DispatcherInterface          $dispatcher
 * @property \ManaPHP\Security\CsrfTokenInterface      $csrfToken
 * @property \ManaPHP\Paginator                        $paginator
 * @property \ManaPHP\Message\QueueInterface           $messageQueue
 * @property \ManaPHP\Security\RateLimiterInterface    $rateLimiter
 * @property \ManaPHP\Meter\LinearInterface            $linearMeter
 * @property \ManaPHP\Meter\RoundInterface             $roundMeter
 * @property \ManaPHP\Security\SecintInterface         $secint
 * @property \ManaPHP\Http\FilterInterface             $filter
 * @property \ManaPHP\Db\Model\MetadataInterface       $modelsMetadata
 * @property \ManaPHP\StopwatchInterface               $stopwatch
 * @property \ManaPHP\Security\HtmlPurifierInterface   $htmlPurifier
 * @property \ManaPHP\RouterInterface                  $router
 */
abstract class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(strtr(get_called_class(), '\\', '.'), 'Controller');
    }
}