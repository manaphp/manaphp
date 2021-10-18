<?php

namespace ManaPHP\Ws;

/**
 * @property-read \ManaPHP\Ws\ServerInterface $wsServer
 */
class Application extends \ManaPHP\Application
{
    /**
     * @return void
     */
    public function authenticate()
    {

    }

    public function main()
    {
        $this->attachEvent('request:authenticate', [$this, 'authenticate']);

        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->wsServer->start();
    }
}