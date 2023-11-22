<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

interface ProcessInterface
{
    public function handle(): void;

    public function isEnabled(): bool;

    public function getNumberOfInstances(): int;

    public function isEnableCoroutine(): bool;

    public function getPipeType(): int;
}