<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\Adapter\Native\SenderInterface;
use ManaPHP\Http\Server\Event\ServerReady;

class Fpm extends AbstractServer
{
    #[Autowired] protected SenderInterface $sender;

    protected function prepareGlobals(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->globals->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);
    }

    public function start(): void
    {
        $this->prepareGlobals();

        $this->bootstrap();

        $this->eventDispatcher->dispatch(new ServerReady());

        $this->httpHandler->handle();
    }

    public function send(): void
    {
        $this->sender->send();
    }

}
