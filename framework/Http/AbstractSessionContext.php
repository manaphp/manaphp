<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class AbstractSessionContext
{
    public ?int $ttl = null;
    public bool $started = false;
    public ?bool $is_new = null;
    public bool $is_dirty = false;
    public ?string $session_id = null;
    public ?array $_SESSION = null;
}