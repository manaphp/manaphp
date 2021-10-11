<?php

namespace ManaPHP\Http;

/**
 * @property-read \ManaPHP\Http\ServerInterface $httpServer
 */
class Application extends \ManaPHP\Application
{
    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
    }

    public function main()
    {
        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->httpServer->start();
    }
}