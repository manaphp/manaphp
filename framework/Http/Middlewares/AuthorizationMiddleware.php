<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Server\Event\RequestAuthorizing;

class AuthorizationMiddleware
{
    #[Autowired] protected AuthorizationInterface $authorization;

    public function onAuthorizing(#[Event] RequestAuthorizing $event): void
    {
        SuppressWarnings::unused($event);

        $this->authorization->authorize();
    }
}