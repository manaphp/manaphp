<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Http\AbstractServer;

/**
 * @property-read \ManaPHP\Http\Server\Adapter\Native\SenderInterface $sender
 */
class Fpm extends AbstractServer
{
    protected function prepareGlobals(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->globals->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);
    }

    public function start(): void
    {
        $this->prepareGlobals();

        $this->fireEvent('httpServer:start');

        $this->httpHandler->handle();
    }

    public function send(): void
    {
        $this->sender->send();
    }

}
