<?php
namespace ManaPHP\WebSocket;

/**
 * Class Controller
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\WebSocket\Dispatcher      $dispatcher
 */
class Controller extends \ManaPHP\Rest\Controller
{
    /**
     * @param int $fd
     *
     * @return string|\JsonSerializable|null
     */
    public function onOpen($fd)
    {

    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return string|\JsonSerializable|null
     */
    public function onMessage($fd, $data)
    {
        return $this->dispatcher->dispatchMessage($data);
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    public function onClose($fd)
    {

    }
}