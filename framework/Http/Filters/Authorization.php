<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Server\Event\RequestAuthorizing;

class Authorization
{
    #[Autowired] protected AuthorizationInterface $authorization;

    public function onAuthorizing(#[Event] RequestAuthorizing $event): void
    {
        $this->authorization->authorize();
    }
}