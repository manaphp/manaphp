<?php

namespace ManaPHP\Ws;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Ws\Server\HandlerInterface;
use Throwable;

/**
 * @property-read \ManaPHP\Ws\ServerInterface            $wsServer
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Http\RouterInterface          $router
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Ws\DispatcherInterface        $dispatcher
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->attachEvent('request:authenticate', [$this, 'authenticate']);
    }

    /**
     * @return string
     */
    public function getFactory()
    {
        return 'ManaPHP\Ws\Factory';
    }

    /**
     * @return void
     */
    public function authenticate()
    {

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
                $this->fireEvent('wsServer:open', compact('fd'));
            } elseif ($event === 'close') {
                $this->fireEvent('wsServer:close', compact('fd'));
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
     *
     * @return void
     */
    public function onOpen($fd)
    {
        $this->handle($fd, 'open');
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    public function onClose($fd)
    {
        $this->handle($fd, 'close');
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return void
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