<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class Plugin
 * @package ManaPHP
 *
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Paginator                      $paginator
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Security\SecintInterface       $secint
 * @property-read \ManaPHP\Http\FilterInterface           $filter
 * @property-read \ManaPHP\Db\Model\MetadataInterface     $modelsMetadata
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                $router
 * @property-read \ManaPHP\AuthorizationInterface         $authorization
 * @property-read \ManaPHP\Http\CookiesInterface          $cookies
 * @property-read \ManaPHP\Http\SessionInterface          $session
 * @property-read \ManaPHP\RendererInterface              $renderer
 */
abstract class Plugin extends Component implements PluginInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Plugin');
    }

    public function init()
    {
        $called_class = get_called_class();

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $rc = new \ReflectionClass($called_class);

        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isStatic()
                && $method->getDeclaringClass()->getName() === $called_class
                && $method->name[0] !== '_'
                && $method->name !== 'init'
            ) {
                $this->eventsManager->attachEvent('request:begin', [$this, $method->name]);
            }
        }
    }
}