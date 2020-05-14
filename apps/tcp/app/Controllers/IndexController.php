<?php

namespace App\Controllers;

use ManaPHP\Socket\Controller;

/**
 * Class TestController
 *
 * @package App\Controllers
 */
class IndexController extends Controller
{
    public function connectAction($fd)
    {
        $this->logger->debug('connect: ' . $fd);
    }

    public function receiveAction($fd, $data)
    {
        $this->logger->debug('receive: ' . $data);
        return $data;
    }

    public function closeAction()
    {
        $this->logger->debug('close');
    }
}
