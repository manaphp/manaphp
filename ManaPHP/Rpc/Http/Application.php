<?php

namespace ManaPHP\Rpc\Http;

use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Http\Response;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Rpc\Http\Server\HandlerInterface;
use Throwable;

/**
 * @property-read \ManaPHP\Rpc\Http\ServerInterface $rpcServer
 * @property-read \ManaPHP\Http\RouterInterface     $router
 * @property-read \ManaPHP\Http\ResponseInterface   $response
 * @property-read \ManaPHP\Rpc\DispatcherInterface  $dispatcher
 * @property-read \ManaPHP\Http\RequestInterface    $request
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    /**
     * @return string
     */
    public function getFactory()
    {
        return 'ManaPHP\Rpc\Http\Factory';
    }

    public function authenticate()
    {
        return true;
    }

    public function handle()
    {
        try {
            $this->fireEvent('request:begin');

            if (!$this->router->match()) {
                throw new NotFoundRouteException(
                    ['router does not have matched route for `%s`', $this->router->getRewriteUri()]
                );
            }

            $actionReturnValue = $this->dispatcher->dispatch(
                $this->router->getArea(), $this->router->getController(), $this->router->getAction(),
                $this->router->getParams()
            );

            if (Reflection::isInstanceOf($actionReturnValue, Response::class)) {
                null;
            } else {
                $this->response->setJsonData($actionReturnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $exception) {
            $this->fireEvent('request:exception', compact('exception'));

            $this->handleException($exception);
        }

        $this->rpcServer->send();

        $this->fireEvent('request:end');
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerConfigure();

        $this->rpcServer->start($this);
    }
}
