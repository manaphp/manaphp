<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Event;

use ManaPHP\Http\AbstractSessionContext;
use ManaPHP\Http\SessionInterface;

class SessionUpdate
{
    public function __construct(
        public SessionInterface $session,
        public AbstractSessionContext $context,
        public string $session_id,
    ) {

    }
}