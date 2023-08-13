<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Ws\Pushing\ServerInterface as PushingServerInterface;

/**
 * @method startAction()
 * @method stopAction()
 * @method openAction($fd)
 * @method closeAction($fd)
 * @method messageAction($fd, $data)
 */
class Controller extends \ManaPHP\Http\Controller
{
    #[Inject]
    protected ServerInterface $wsServer;
    #[Inject]
    protected RequestInterface $request;
    #[Inject]
    protected ResponseInterface $response;
    #[Inject]
    protected RouterInterface $router;
    #[Inject]
    protected PushingServerInterface $wspServer;
}