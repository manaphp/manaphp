<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\AbstractSessionContext;
use ManaPHP\Http\SessionInterface;

#[Verbosity(Verbosity::MEDIUM)]
class SessionUpdate implements JsonSerializable
{
    public function __construct(
        public SessionInterface $session,
        public AbstractSessionContext $context,
        public string $session_id,
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'session_id' => $this->session_id,
            'SESSION'    => $this->context->_SESSION,
        ];
    }
}