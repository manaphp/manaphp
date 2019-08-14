<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class Controller
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 * @property-read \ManaPHP\Http\RequestInterface     $request
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\DispatcherInterface       $dispatcher
 * @property-read \ManaPHP\RouterInterface           $router
 */
class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }
}