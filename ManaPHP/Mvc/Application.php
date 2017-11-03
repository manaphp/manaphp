<?php

namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property \ManaPHP\Mvc\HandlerInterface   $mvcHandler
 * @property \ManaPHP\Http\ResponseInterface $response
 */
class Application extends \ManaPHP\Application
{
    public function main()
    {
        $this->registerServices();

        $this->configure->debug && $this->debugger->start();

        $this->mvcHandler->handle()->send();
    }
}