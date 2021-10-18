<?php

namespace ManaPHP\Rpc\Http;

/**
 * @property-read \ManaPHP\Rpc\ServerInterface $rpcServer
 */
class Application extends \ManaPHP\Application
{
    public function main()
    {
        $this->dotenv->load();
        $this->config->load();

        $this->configure();

        $this->rpcServer->start();
    }
}
