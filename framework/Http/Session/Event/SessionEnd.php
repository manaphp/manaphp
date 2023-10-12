<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\AbstractSessionContext;
use ManaPHP\Http\SessionInterface;

#[Verbosity(Verbosity::HIGH)]
class SessionEnd
{
    public function __construct(
        public SessionInterface $session,
        public AbstractSessionContext $context,
        public string $session_id,
    ) {

    }
}