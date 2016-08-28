<?php

namespace ManaPHP\Mvc;

/**
 * ManaPHP\Mvc\ModuleInterface initializer
 *
 * @property \ManaPHP\Http\SessionInterface   $session
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Http\ResponseInterface  $response
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \Application\Configure           $configure
 * @property \ManaPHP\Http\ClientInterface    $httpClient
 */
interface ModuleInterface
{
    /**
     * Registers services related to the module
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function registerServices($dependencyInjector);

    /**
     * @param string $controller
     * @param string $action
     *
     * @return false|void
     */
    public function authorize($controller, $action);
}