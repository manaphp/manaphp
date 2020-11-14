<?php

namespace ManaPHP\WebSocket;

/**
 * Class Controller
 *
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\Http\RequestInterface     $request
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\Http\RouterInterface      $router
 * @method startAction()
 * @method stopAction()
 * @method openAction($fd)
 * @method closeAction($fd)
 * @method messageAction($fd, $data)
 *
 */
class Controller extends \ManaPHP\Controller
{

}