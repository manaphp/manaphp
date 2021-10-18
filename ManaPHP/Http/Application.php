<?php

namespace ManaPHP\Http;

use ManaPHP\Configurators\PluginConfigurator;
use ManaPHP\Configurators\TracerConfigurator;
use ManaPHP\Configurators\MiddlewareConfigurator;

/**
 * @property-read \ManaPHP\Http\ServerInterface $httpServer
 */
class Application extends \ManaPHP\Application
{
    protected $configurators
        = [
            PluginConfigurator::class,
            TracerConfigurator::class,
            MiddlewareConfigurator::class,
        ];

    public function main()
    {
        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->httpServer->start();
    }
}