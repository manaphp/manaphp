<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Autowired;
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
    #[Autowired] protected ServerInterface $wsServer;
    #[Autowired] protected PushingServerInterface $wspServer;
}