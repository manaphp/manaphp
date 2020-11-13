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
        parent::__construct($loader);

        $this->attachEvent('request:authenticate', [$this, 'authenticate']);
    }

    public function getFactory()
    {
        return 'ManaPHP\WebSocket\Factory';
    }

    public function authenticate()
    {
        $this->identity->authenticate();
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

            if ($event === 'open') {
                $this->fireEvent('request:authenticate');
            }

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

            if ($event === 'open') {
                $this->fireEvent('wsServer:open', $fd);
            } elseif ($event === 'close') {
                $this->fireEvent('wsServer:close', $fd);
            }
        } catch (AbortException $exception) {
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

    /**
     * @param int $fd
     */
    public function onOpen($fd)
    {
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