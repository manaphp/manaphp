<?php

namespace ManaPHP\WebSocket;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Router\NotFoundRouteException;
use ManaPHP\WebSocket\Server\HandlerInterface;
use Throwable;

/**
 * Class Application
 *
 * @package ManaPHP\WebSocket
 *
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\RouterInterface           $router
 * @property-read \ManaPHP\Http\RequestInterface     $request
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\DispatcherInterface       $dispatcher
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        define('MANAPHP_CLI', false);

        parent::__construct($loader);
    }

    public function getFactory()
    {
        return 'ManaPHP\WebSocket\Factory';
    }

    /**
     * @param int    $fd
     * @param string $event
     *
     * @return void
     */
    public function handle($fd, $event)
    {
        try {
            $throwable = null;

            $this->fireEvent('request:begin');

            if (!$this->router->match()) {
                throw new NotFoundRouteException(['router does not have matched route']);
            }

            $this->router->setAction($event);

            $returnValue = $this->dispatcher->dispatch($this->router);

            if ($returnValue === null || $returnValue instanceof Response) {
                null;
            } elseif (is_string($returnValue)) {
                $this->response->setJsonError($returnValue);
            } elseif (is_array($returnValue)) {
                $this->response->setJsonData($returnValue);
            } elseif (is_int($returnValue)) {
                $this->response->setJsonError('', $returnValue);
            } else {
                $this->response->setJsonContent($returnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }

        try {
            if ($event === 'open') {
                $this->fireEvent('ws:open', $fd);
            } elseif ($event === 'close') {
                $this->fireEvent('ws:close', $fd);
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }

        if ($content = $this->response->getContent()) {
            $this->wsServer->push($fd, $content);
        }

        $this->fireEvent('request:end');

        if ($throwable) {
            $this->wsServer->disconnect($fd);
        }
    }

    public function onStart($worker_id)
    {
        $this->fireEvent('ws:start', $worker_id);
    }

    /**
     * @param int $fd
     */
    public function onOpen($fd)
    {
        $globals = $this->request->getContext();
        $globals->_REQUEST['fd'] = $fd;

        $this->handle($fd, 'open');
    }

    /**
     * @param int $fd
     */
    public function onClose($fd)
    {
        $this->handle($fd, 'close');
    }

    /**
     * @param int    $fd
     * @param string $data
     */
    public function onMessage($fd, $data)
    {
        $globals = $this->request->getContext();
        $globals->_REQUEST['data'] = $data;

        $this->handle($fd, 'message');
        $globals->_REQUEST['data'] = null;
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerConfigure();

        $this->wsServer->start($this);
    }
}