<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Server\Event\RequestAuthorizing;

class AuthorizationFilter
{
    #[Inject] protected AuthorizationInterface $authorization;

    public function onAuthorizing(#[Event] RequestAuthorizing $event): void
    {
        $this->authorization->authorize();
    }
}