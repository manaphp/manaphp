<?php
namespace ManaPHP\Rpc;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Rpc\Server\HandlerInterface;
use Throwable;

/**
 * Class Application
 * @package ManaPHP\Rpc
 *
 * @property-read \ManaPHP\Rpc\ServerInterface $rpcServer
 * @property-read \ManaPHP\RouterInterface     $router
 * @property-read \ManaPHP\Http\Response       $response
 * @property-read \ManaPHP\Rpc\Dispatcher      $dispatcher
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        if (PHP_SAPI === 'cli') {
            if (extension_loaded('swoole')) {
                $this->getDi()->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Swoole');
            } else {
                $this->getDi()->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->getDi()->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Php');
        } else {
            $this->getDi()->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Fpm');
        }
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function handle()
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue === null || $actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }

        $this->rpcServer->send($this->response->_context);

        $this->eventsManager->fireEvent('request:end', $this);
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->rpcServer->start($this);
    }
}