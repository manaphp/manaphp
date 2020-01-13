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
    public function getVerbs()
    {
        return [
            'index' => 'GET',
            'list' => 'GET',
            'detail' => 'GET',
            'captcha' => 'GET',
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

    /**
     * @return array
     */
    public function getRateLimit()
    {
        return [];
    }

    /**
     * @return array =[$field => ["etag", "max-age"=>1, "Cache-Control"=>"private, max-age=0, no-store, no-cache, must-revalidate"]]
     */
    public function getHttpCache()
    {
        return [];
    }
}
