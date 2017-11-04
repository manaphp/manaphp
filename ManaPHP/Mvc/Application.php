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
    public function registerServices()
    {
        parent::registerServices();

        $this->_dependencyInjector->router->mount($this->configure->modules);
    }

    public function main()
    {
        $this->registerServices();

        $this->configure->debug && $this->debugger->start();

        $this->mvcHandler->handle()->send();
    }
}