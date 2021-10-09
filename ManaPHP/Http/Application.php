<?php

namespace ManaPHP\Http;

/**
 * @property-read \ManaPHP\Http\ServerInterface $httpServer
 *
 * @method void authorize()
 */
abstract class Application extends \ManaPHP\Application
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

    public function main()
    {
        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->httpServer->start();
    }
}