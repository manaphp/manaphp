<?php
declare(strict_types=1);

namespace ManaPHP\Identifying;

use ManaPHP\Coroutine\Context\Stickyable;

class IdentityContext implements Stickyable
{
    public array $claims = [];
}