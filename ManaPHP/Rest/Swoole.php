<?php
namespace ManaPHP\Rest;

use ManaPHP\ContextManager;
use ManaPHP\Http\Response;
use Swoole\Runtime;

/**
 * Class ManaPHP\Rest\Swoole
 *
 * @package application
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\RouterInterface             $router
 * @property-read \ManaPHP\DispatcherInterface         $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface       $session
 * @property-read \ManaPHP\Swoole\Http\ServerInterface $swooleHttpServer
 */
class Swoole extends \ManaPHP\Application
{
    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
            $this->_di->setShared('swooleHttpServer', 'ManaPHP\Swoole\Http\Server');
        }

        return $this->_di;
    }

    public function send()
    {
        $swoole = $this->swooleHttpServer;
        $response = $this->response;

        if (($request_id = $this->request->getServer('HTTP_X_REQUEST_ID')) && !$response->hasHeader('X-Request-Id')) {
            $response->setHeader('X-Request-Id', $request_id);
        }

        $response->setHeader('X-Response-Time', sprintf('%.3f', microtime(true) - $this->request->getServer('REQUEST_TIME_FLOAT')));

        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        $swoole->setStatus($response->getStatusCode());
        $swoole->sendHeaders($response->getHeaders());

        if ($file = $response->getFile()) {
            $swoole->sendFile($file);
        } else {
            $swoole->sendContent($response->getContent());
        }

        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
    }

    public function handle()
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->authenticate();

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue !== null && !$actionReturnValue instanceof Response) {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $exception) {
            $this->handleException($exception);
        } catch (\Error $error) {
            $this->handleException($error);
        }

        $this->send();

        $this->eventsManager->fireEvent('request:destruct', $this);
        $this->eventsManager->fireEvent('request:end', $this);

        ContextManager::reset();
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        if (MANAPHP_COROUTINE) {
            Runtime::enableCoroutine();
        }

        $this->registerServices();

        $this->eventsManager->fireEvent('app:start', $this);

        $this->swooleHttpServer->start([$this, 'handle']);
    }
}