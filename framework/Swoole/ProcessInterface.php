<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

interface ProcessInterface
{
    public const SETTINGS_NUMS = 'nums';
    public const SETTINGS_PIPE_TYPE = 'pipe_type';
    public const SETTINGS_ENABLE_COROUTINE = 'enable_coroutine';

    public function handle(): void;

    public function isEnabled(): bool;

    public function getSettings(): array;
}