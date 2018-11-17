<?php

namespace ManaPHP\Mvc;

use ManaPHP\Exception\AuthenticationException;
use ManaPHP\Http\Response;
use ManaPHP\Router\NotFoundRouteException;
use ManaPHP\View;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\ViewInterface          $view
 * @property-read \ManaPHP\Http\SessionInterface  $session
 * @property-read \ManaPHP\AuthorizationInterface $authorization
 */
class Application extends \ManaPHP\Application
{
    /**
     * @var string
     */
    protected $_loginUrl = '/user/session/login';

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->attachEvent('dispatcher:beforeInvoke', [$this, 'authorize']);
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }
        return $this->_di;
    }

    public function authenticate()
    {
        return $this->identity->authenticate();
    }

    public function authorize()
    {
        try {
            $this->authorization->authorize();
        } catch (AuthenticationException $exception) {
            if ($this->request->isAjax()) {
                return $this->response->setJsonContent($exception);
            } else {
                $redirect = $this->request->get('redirect', null, $this->request->getUrl());
                $sep = (strpos($this->_loginUrl, '?') ? '&' : '?');
                return $this->response->redirect(["{$this->_loginUrl}{$sep}redirect=$redirect"]);
            }
        }

        return null;
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
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->view->render();
                $this->response->setContent($this->view->getContent());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (\Error $e) {
            $this->handleException($e);
        }

        $this->response->send();

        $this->fireEvent('app:endRequest');
    }
}