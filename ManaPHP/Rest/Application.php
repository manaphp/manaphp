<?php

namespace ManaPHP\Rest;

use ManaPHP\Http\Response;
use ManaPHP\Router\NotFoundRouteException;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface  $session
 * @property-read \ManaPHP\AuthorizationInterface $authorization
 */
class Application extends \ManaPHP\Application
{
    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function main()
    {
        try {
            $this->dotenv->load();
            $this->configure->load();

            $this->registerServices();

            $this->fireEvent('app:start');

            $this->fireEvent('app:beginRequest');

            $this->authenticate();

            if (!$this->router->match()) {
                throw new NotFoundRouteException(['router does not have matched route for `:uri`', 'uri' => $this->router->getRewriteUri()]);
            }

            $this->dispatcher->dispatch($this->router);
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue !== null && !$actionReturnValue instanceof Response) {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $exception) {
            $this->handleException($exception);
        } catch (\Error $error) {
            $this->handleException($error);
        }

        $this->response->send();

        $this->fireEvent('app:endRequest');
    }
}