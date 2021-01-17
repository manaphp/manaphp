<?php

namespace ManaPHP\Socket;

use ManaPHP\Socket\Server\HandlerInterface;

/**
 * @property-read \ManaPHP\Socket\ServerInterface   $socketServer
 * @property-read \ManaPHP\Socket\RequestInterface  $request
 * @property-read \ManaPHP\Socket\ResponseInterface $response
 */
class Application extends \ManaPHP\Application implements HandlerInterface
{
    /**
     * @return string
     */
    public function getFactory()
    {
        return 'ManaPHP\Socket\Factory';
    }

    /**
     * @param string $action
     *
     * @return void
     */
    public function invoke($action)
    {
        static $controller;
        if ($controller === null) {
            $controller = $this->getShared('App\Controllers\IndexController');
        }

        if (($r = $controller->invoke($action)) !== null) {
            $this->response->setContent($r);
        }
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    public function onConnect($fd)
    {
        $request_context = $this->request->getContext();
        $request_context->_SERVER = $this->socketServer->getClientInfo($fd);
        $request_context->_REQUEST['fd'] = $fd;

        $this->fireEvent('socketServer:connecting', compact('fd'));

        $this->invoke('connect');
        $this->fireEvent('socketServer:connected', compact('fd'));

        $response = $this->response->getContext();
        if ($response->content !== null) {
            $this->send($fd, $response->content);
        }
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return void
     */
    public function onReceive($fd, $data)
    {
        $request_context = $this->request->getContext();
        $request_context->_SERVER = $this->socketServer->getClientInfo($fd);
        $request_context->_REQUEST['fd'] = $fd;
        $request_context->_REQUEST['data'] = $data;

        $this->fireEvent('socketServer:receiving', compact('fd', 'data'));
        $this->invoke('receive');
        $this->fireEvent('socketServer:received', compact('fd', 'data'));

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

        $this->fireEvent('socketServer:closing', compact('fd'));
        $this->invoke('close');
        $this->fireEvent('socketServer:closed', compact('fd'));
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerConfigure();

        $this->socketServer->start($this);
    }
}