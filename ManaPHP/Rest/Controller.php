<?php

namespace ManaPHP\Rest;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method bool beforeInvoke(string $action);
 * @method bool afterInvoke(string $action, mixed $r);
 *
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\PasswordInterface              $password
 * @property-read \ManaPHP\CounterInterface               $counter
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Security\CsrfTokenInterface    $csrfToken
 * @property-read \ManaPHP\Paginator                      $paginator
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Security\RateLimiterInterface  $rateLimiter
 * @property-read \ManaPHP\Meter\LinearInterface          $linearMeter
 * @property-read \ManaPHP\Meter\RoundInterface           $roundMeter
 * @property-read \ManaPHP\Security\SecintInterface       $secint
 * @property-read \ManaPHP\Http\FilterInterface           $filter
 * @property-read \ManaPHP\Db\Model\MetadataInterface     $modelsMetadata
 * @property-read \ManaPHP\StopwatchInterface             $stopwatch
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                $router
 */
abstract class Controller extends \ManaPHP\Controller
{

}