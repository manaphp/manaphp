<?php
namespace ManaPHP\WebSocket;

use ManaPHP\ContextManager;
use ManaPHP\Http\Response;

/**
 * Class Application
 * @package ManaPHP\WebSocket
 *
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\RouterInterface           $router
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\DispatcherInterface       $dispatcher
 */
class Application extends \ManaPHP\Application implements ApplicationInterface
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->eventsManager->attachEvent('request:authenticate', [$this, 'authenticate']);

        if (method_exists($this, 'authorize')) {
            $this->eventsManager->attachEvent('request:authorize', [$this, 'authorize']);
        }
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function authenticate()
    {
        $this->identity->authenticate();
    }

    /**
     * @param int $fd
     */
    public function onOpen($fd)
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->eventsManager->fireEvent('request:authenticate', $this);
            $this->eventsManager->fireEvent('ws:open', $fd);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue === null || $actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }

        $content = $this->response->getContent();
        if ($content !== null && $content !== '') {
            $this->wsServer->push($fd, $content);
            $this->response->setContent('');
        }
    }

    /**
     * @param int $fd
     */
    public function onClose($fd)
    {
        try {
            $this->eventsManager->fireEvent('ws:close', $fd);
            $this->eventsManager->fireEvent('request:destruct', $this);
            $this->eventsManager->fireEvent('request:end', $this);
        } finally {
            ContextManager::reset();
        }
    }

    /**
     * @param int    $fd
     * @param string $data
     */
    public function onMessage($fd, $data)
    {
        $this->eventsManager->fireEvent('ws:message', $this, compact('fd', 'data'));

        $globals = $this->request->getGlobals();
        if (!is_array($post = json_decode($data, true, 16))) {
            $post['raw_body'] = $data;
        }
        $globals->_POST = $post;
        $globals->_REQUEST = $post + $globals->_GET;

        try {
            $actionReturnValue = $this->dispatcher->invoke();

            if ($actionReturnValue === null || $actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }

        $this->wsServer->push($fd, $this->response->getContent());
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->wsServer->start();
    }
}