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

    protected function _prepareGlobals()
    {
        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->configure->compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    public function main()
    {
        try {
            $this->dotenv->load();
            $this->configure->load();

            $this->registerServices();

            $this->fireEvent('app:start');

            $this->_prepareGlobals();

            $this->fireEvent('request:begin');
            $this->fireEvent('request:init');

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

        $this->fireEvent('request:destroy');
        $this->fireEvent('request:end');
    }
}