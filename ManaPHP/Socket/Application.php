<?php

namespace ManaPHP\Socket;

/**
 * @property-read \ManaPHP\Socket\ServerInterface   $socketServer
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

        $this->socketServer->start();
    }
}