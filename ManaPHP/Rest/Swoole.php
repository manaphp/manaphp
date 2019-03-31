<?php
namespace ManaPHP\Rest;

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
class Swoole extends \ManaPHP\Http\Application
{
    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
            $this->_di->setShared('swooleHttpServer', 'ManaPHP\Swoole\Http\Server');
        }

        return $this->_di;
    }

    public function handle()
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->eventsManager->fireEvent('request:authenticate', $this);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue !== null && !$actionReturnValue instanceof Response) {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $exception) {
            $this->handleException($exception);
        } catch (\Error $error) {
            $this->handleException($error);
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