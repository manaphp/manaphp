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
 * @property-read \ManaPHP\Http\RequestInterface   $request
 * @property-read \ManaPHP\Http\ResponseInterface  $response
 * @property-read \ManaPHP\RouterInterface         $router
 * @property-read \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\ViewInterface           $view
 * @property-read \ManaPHP\Http\SessionInterface   $session
 * @property-read \ManaPHP\AuthorizationInterface  $authorization
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

        $routerClass = $this->alias->resolveNS('@ns.app\Router');
        if (class_exists($routerClass)) {
            $this->_di->setShared('router', $routerClass);
        }

        $this->attachEvent('actionInvoker:beforeInvoke', [$this, 'authorize']);
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
    }

    /**
     * @return \ManaPHP\Http\ResponseInterface
     * @throws \ManaPHP\Router\NotFoundRouteException
     */
    public function handle()
    {
        $this->authenticate();

        if (!$this->router->handle()) {
            throw new NotFoundRouteException(['router does not have matched route for `:uri`', 'uri' => $this->router->getRewriteUri()]);
        }

        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();

        $ret = $this->dispatcher->dispatch($controllerName, $actionName, $params);
        if ($ret !== false) {
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->view->render();
                $this->response->setContent($this->view->getContent());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        }

        return $this->response;
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        try {
            $this->handle();
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
        } catch (\Error $e) {
            $this->errorHandler->handle($e);
        }

        if (isset($_SERVER['HTTP_X_REQUEST_ID']) && !$this->response->hasHeader('X-Request-Id')) {
            $this->response->setHeader('X-Request-Id', $_SERVER['HTTP_X_REQUEST_ID']);
        }

        $this->response->send();
    }
}