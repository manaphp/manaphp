<?php
namespace ManaPHP\Http;

/**
 * Class Controller
 * @package ManaPHP\Http
 *
 * @property-read \ManaPHP\InvokerInterface               $invoker
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\RouterInterface                $router
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 */
abstract class Controller extends \ManaPHP\Controller
{
    /**
     * @return array
     */
    public function getAcl()
    {
        return [];
    }

    /**
     * @return array
     */
    abstract public function getVerbs();
}
