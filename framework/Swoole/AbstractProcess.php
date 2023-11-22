<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\Process\Attribute\Settings;

#[Settings(nums: 2)]
abstract class AbstractProcess implements ProcessInterface
{
    #[Autowired] protected bool $enabled = true;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}