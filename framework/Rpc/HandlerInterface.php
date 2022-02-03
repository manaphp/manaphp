<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

interface HandlerInterface
{
    public function authenticate(): bool;

    public function handle(): void;
}