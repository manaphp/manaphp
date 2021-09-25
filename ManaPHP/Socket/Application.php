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
    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
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
        $this->request->prepare(compact('fd'), $this->socketServer->getClientInfo($fd));
        $this->fireEvent('socketServer:connecting', compact('fd'));

        $this->invoke('connect');
        $this->fireEvent('socketServer:connected', compact('fd'));

        if (($content = $this->response->getContent()) !== null) {
            $this->send($fd, $content);
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
        $this->request->prepare(compact('fd', 'data'), $this->socketServer->getClientInfo($fd));

        $this->fireEvent('socketServer:receiving', compact('fd', 'data'));
        $this->invoke('receive');
        $this->fireEvent('socketServer:received', compact('fd', 'data'));

        if (($content = $this->response->getContent()) !== null) {
            $this->send($fd, $content);
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
        $this->request->prepare(compact('fd'), $this->socketServer->getClientInfo($fd));

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