<?php

namespace ManaPHP\Rpc;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Rpc\Server\HandlerInterface;
use Throwable;

/**
 * Class Application
 *
 * @package ManaPHP\Rpc
 *
 * @property-read \ManaPHP\Rpc\ServerInterface    $rpcServer
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\Http\RequestInterface  $request
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        define('MANAPHP_CLI', false);

        parent::__construct($loader);

        if ($_SERVER['DOCUMENT_ROOT'] === '') {
            $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
        }
    }

    public function getFactory()
    {
        return 'ManaPHP\Rpc\Factory';
    }

    public function authenticate()
    {
        return true;
    }

    public function handle()
    {
        try {
            $this->fireEvent('request:begin');

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue instanceof Response) {
                null;
            } elseif ($actionReturnValue instanceof Throwable) {
                $this->response->setJsonContent($actionReturnValue);
            } else {
                $this->response->setJsonData($actionReturnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->fireEvent('request:exception', $throwable);

            $this->handleException($throwable);
        }

        $this->rpcServer->send($this->response->getContext());

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
