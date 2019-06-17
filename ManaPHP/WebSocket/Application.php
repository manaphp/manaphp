<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Http\Response;
use ManaPHP\WebSocket\Server\HandlerInterface;
use Throwable;

/**
 * Class Application
 * @package ManaPHP\WebSocket
 *
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\RouterInterface           $router
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\WebSocket\Dispatcher      $dispatcher
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        define('MANAPHP_COROUTINE', true);

        parent::__construct($loader);
    }

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
     * @param int $fd
     */
    public function onOpen($fd)
    {
        try {
            $throwable = null;

            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->router->match();
            $this->dispatcher->dispatch($this->router, false);

            $returnValue = $this->dispatcher->getControllerInstance()->onOpen($fd);
            if ($returnValue === null || $returnValue instanceof Response) {
                null;
            } elseif (is_string($returnValue)) {
                $this->response->setJsonError($returnValue);
            } else {
                $this->response->setJsonContent($returnValue);
            }

            $this->eventsManager->fireEvent('ws:open', $this, $fd);
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }

        $content = $this->response->getContent();
        if ($content !== null && $content !== '') {
            $this->wsServer->push($fd, $content);
            $this->response->setContent(null);
        }

        if ($throwable) {
            $this->wsServer->disconnect($fd);
        }
    }

    /**
     * @param int $fd
     */
    public function onClose($fd)
    {
        $this->dispatcher->getControllerInstance()->onClose($fd);
        $this->eventsManager->fireEvent('ws:close', $this, $fd);
        $this->eventsManager->fireEvent('request:destruct', $this);
        $this->eventsManager->fireEvent('request:end', $this);
    }

    /**
     * @param int    $fd
     * @param string $data
     */
    public function onMessage($fd, $data)
    {
        $this->eventsManager->fireEvent('ws:message', $this, compact('fd', 'data'));

        try {
            $returnValue = $this->dispatcher->getControllerInstance()->onMessage($fd, $data);

            if ($returnValue === null || $returnValue instanceof Response) {
                null;
            } elseif (is_string($returnValue)) {
                $this->response->setJsonError($returnValue);
            } else {
                $this->response->setJsonContent($returnValue);
            }
        } catch (Throwable $exception) {
            $this->handleException($exception);
        }

        $content = $this->response->getContent();
        if ($content !== null && $content !== '') {
            $this->wsServer->push($fd, $content);
            $this->response->setContent(null);
        }
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->wsServer->start($this);
    }
}