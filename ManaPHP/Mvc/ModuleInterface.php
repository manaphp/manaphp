<?php

namespace ManaPHP\Mvc;

/**
 * ManaPHP\Mvc\ModuleInterface initializer
 *
 * @property \ManaPHP\Http\Session   $session
 * @property \ManaPHP\Http\Request   $request
 * @property \ManaPHP\Http\Response  $response
 * @property \ManaPHP\Mvc\Dispatcher $dispatcher
 * @property \Application\Configure  $configure
 * @property \ManaPHP\Http\Client    $httpClient
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