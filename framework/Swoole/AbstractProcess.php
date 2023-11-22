<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;

abstract class AbstractProcess implements ProcessInterface
{
    #[Autowired] protected bool $enabled = true;
    #[Autowired] protected array $settings = [];

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getSettings(): array
    {
        return $this->settings + [
                self::SETTINGS_NUMS             => 1,
                self::SETTINGS_PIPE_TYPE        => SOCK_DGRAM,
                self::SETTINGS_ENABLE_COROUTINE => true,
            ];
    }
}