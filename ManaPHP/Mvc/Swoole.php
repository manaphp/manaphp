<?php

namespace ManaPHP\Mvc;

use ManaPHP\Exception\AuthenticationException;
use ManaPHP\Http\Response;
use ManaPHP\View;
use Swoole\Runtime;

/**
 * Class ManaPHP\Mvc\Swoole
 *
 * @package application
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\RouterInterface             $router
 * @property-read \ManaPHP\DispatcherInterface         $dispatcher
 * @property-read \ManaPHP\ViewInterface               $view
 * @property-read \ManaPHP\Http\SessionInterface       $session
 * @property-read \ManaPHP\Swoole\Http\ServerInterface $swooleHttpServer
 */
class Swoole extends \ManaPHP\Http\Application
{
    /**
     * @var string
     */
    protected $_loginUrl = '/user/session/login';

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
            $this->_di->setShared('swooleHttpServer', 'ManaPHP\Swoole\Http\Server');
        }
        return $this->_di;
    }

    public function authorize()
    {
        try {
            $this->authorization->authorize();
        } catch (AuthenticationException $exception) {
            if ($this->request->isAjax()) {
                return $this->response->setJsonContent($exception);
            } else {
                $redirect = $this->request->get('redirect', $this->request->getUrl());
                $sep = (strpos($this->_loginUrl, '?') ? '&' : '?');
                return $this->response->redirect(["{$this->_loginUrl}{$sep}redirect=$redirect"]);
            }
        }

        return null;
    }

    public function handle()
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->eventsManager->fireEvent('request:authenticate', $this);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->view->render();
                $this->response->setContent($this->view->getContent());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } elseif ($this->dispatcher->getControllerInstance() instanceof \ManaPHP\Rest\Controller) {
                $this->response->setJsonContent($actionReturnValue);
            } else {
                $this->response->setContent($actionReturnValue);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (\Error $e) {
            $this->handleException($e);
        }

        $this->swooleHttpServer->send($this->response);

        $this->eventsManager->fireEvent('request:destruct', $this);
        $this->eventsManager->fireEvent('request:end', $this);
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        if (MANAPHP_COROUTINE) {
            Runtime::enableCoroutine();
        }

        $this->registerServices();

        $this->swooleHttpServer->start([$this, 'handle']);
    }
}