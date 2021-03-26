<?php

namespace App\Controllers;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
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
