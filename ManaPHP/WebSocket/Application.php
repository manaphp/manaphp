<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Router\NotFoundRouteException;
use ManaPHP\WebSocket\Server\HandlerInterface;
use Throwable;

/**
 * Class Application
 * @package ManaPHP\WebSocket
 *
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\Router                    $router
 * @property-read \ManaPHP\Http\Response             $response
 * @property-read \ManaPHP\DispatcherInterface       $dispatcher
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function getProcesses()
    {
        return [];
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

            $this->eventsManager->fireEvent('request:begin', $this);

            if (!$this->router->match()) {
                throw new NotFoundRouteException(['router does not have matched route']);
            }

            $router = $this->router->_context;
            $router->action = $event;

            $returnValue = $this->dispatcher->dispatch($router);

            if ($returnValue === null || $returnValue instanceof Response) {
                null;
            } elseif (is_string($returnValue)) {
                $this->response->setJsonError($returnValue);
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
                $this->eventsManager->fireEvent('ws:open', $this, $fd);
            } elseif ($event === 'close') {
                $this->eventsManager->fireEvent('ws:close', $this, $fd);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }

        if ($content = $this->response->getContent()) {
            $this->wsServer->push($fd, $content);
        }

        $this->eventsManager->fireEvent('request:end', $this);

        if ($throwable) {
            $this->wsServer->disconnect($fd);
        }
    }

    /**
     * @param int $fd
     */
    public function onOpen($fd)
    {
        $globals = $this->request->getGlobals();
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
        $globals = $this->request->getGlobals();
        $globals->_REQUEST['data'] = $data;

        $this->handle($fd, 'message');
        $globals->_REQUEST['data'] = null;
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->wsServer->start($this);
    }
}