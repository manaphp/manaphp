<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Server\Event\RequestAuthorizing;

class AuthorizationFilter
{
    #[Autowired] protected AuthorizationInterface $authorization;

    public function onAuthorizing(#[Event] RequestAuthorizing $event): void
    {
        $this->authorization->authorize();
    }
}