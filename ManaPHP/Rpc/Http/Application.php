<?php

namespace ManaPHP\Rpc\Http;

/**
 * @property-read \ManaPHP\Rpc\ServerInterface $rpcServer
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

        $this->rpcServer->start();
    }
}
