<?php

namespace ManaPHP\Socket;

use ManaPHP\Socket\Server\HandlerInterface;

/**
 * Class Application
 *
 * @package ManaPHP\Socket
 * @property-read \ManaPHP\Socket\ServerInterface   $socketServer
 * @property-read \ManaPHP\Socket\RequestInterface  $request
 * @property-read \ManaPHP\Socket\ResponseInterface $response
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        define('MANAPHP_CLI', false);

        parent::__construct($loader);
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function invoke($action)
    {
        static $controller;
        if ($controller === null) {
            $controller = $this->_di->getShared('App\Controllers\IndexController');
        }

        if (($r = $controller->invoke($action)) !== null) {
            $this->response->setContent($r);
        }
    }

    public function onConnect($fd)
    {
        $request_context = $this->request->getContext();
        $request_context->_SERVER = $this->socketServer->getClientInfo($fd);
        $request_context->_REQUEST['fd'] = $fd;

        $this->fireEvent('socketServer:connecting', $fd);

        $this->invoke('connect');
        $this->fireEvent('socketServer:connected', $fd);

        $response = $this->response->getContext();
        if ($response->content !== null) {
            $this->send($fd, $response->content);
        }
    }

    public function onReceive($fd, $data)
    {
        $event_data = compact('fd', 'data');

        $request_context = $this->request->getContext();
        $request_context->_SERVER = $this->socketServer->getClientInfo($fd);
        $request_context->_REQUEST['fd'] = $fd;
        $request_context->_REQUEST['data'] = $data;

        $this->fireEvent('socketServer:receiving', $event_data);
        $this->invoke('receive');
        $this->fireEvent('socketServer:received', $event_data);

        $response = $this->response->getContext();
        if ($response->content !== null) {
            $this->send($fd, $response->content);
        }
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return bool
     */
    public function send($fd, $data)
    {
        return $this->socketServer->send($fd, $data);
    }

    public function onClose($fd)
    {
        $request_context = $this->request->getContext();
        $request_context->_SERVER = $this->socketServer->getClientInfo($fd);
        $request_context->_REQUEST['fd'] = $fd;

        $this->fireEvent('socketServer:closing', $fd);
        $this->invoke('close');
        $this->fireEvent('socketServer:closed', $fd);
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->socketServer->start($this);
    }
}