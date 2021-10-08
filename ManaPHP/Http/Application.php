<?php

namespace ManaPHP\Http;

use ManaPHP\Http\Server\HandlerInterface;

/**
 * @property-read \ManaPHP\Http\ServerInterface        $httpServer
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\Http\RouterInterface        $router
 * @property-read \ManaPHP\Http\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface       $session
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 *
 * @method void authorize()
 */
abstract class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->attachEvent('request:authenticate', [$this, 'authenticate']);

        if (method_exists($this, 'authorize')) {
            $this->attachEvent('request:authorize', [$this, 'authorize']);
        }
    }

    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
    }

    /**
     * @return void
     */
    public function authenticate()
    {

    }

    abstract public function handle();

    public function main()
    {
        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->httpServer->start($this);
    }
}