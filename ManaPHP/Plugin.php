<?php

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Http\CaptchaInterface           $captcha
 * @property-read \ManaPHP\Http\RequestInterface           $request
 * @property-read \ManaPHP\Http\ResponseInterface          $response
 * @property-read \ManaPHP\Http\DispatcherInterface        $dispatcher
 * @property-read \ManaPHP\Messaging\QueueInterface        $msgQueue
 * @property-read \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata
 * @property-read \ManaPHP\Html\PurifierInterface          $htmlPurifier
 * @property-read \ManaPHP\Http\RouterInterface            $router
 * @property-read \ManaPHP\Http\AuthorizationInterface     $authorization
 * @property-read \ManaPHP\Http\CookiesInterface           $cookies
 * @property-read \ManaPHP\Http\SessionInterface           $session
 * @property-read \ManaPHP\Html\RendererInterface          $renderer
 */
abstract class Plugin extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Plugin');
    }
}