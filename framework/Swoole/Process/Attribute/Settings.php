<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Process\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Settings
{
    public function __construct(
        public int $nums = 1,
        public int $pipe_type = SOCK_DGRAM,
        public bool $enable_coroutine = true
    ) {

    }
}