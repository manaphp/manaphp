<?php

namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\ModuleInterface
 *
 * @package module
 *
 * @property \ManaPHP\Http\SessionInterface                $session
 * @property \ManaPHP\Http\RequestInterface                $request
 * @property \ManaPHP\Http\ResponseInterface               $response
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Configure                            $configure
 * @property \ManaPHP\Http\ClientInterface                 $httpClient
 * @property \ManaPHP\Security\RateLimiterInterface        $rateLimiter
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\AuthorizationInterface               $authorization
 * @property \ManaPHP\Security\CsrfTokenInterface          $csrfToken
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
     * @return void
     */
    public function antiCsrf();

    /**
     * @return void
     */
    public function authenticate();

    /**
     * @param string $controller
     * @param string $action
     *
     * @return \ManaPHP\Http\ResponseInterface|false|void
     */
    public function authorize($controller, $action);
}