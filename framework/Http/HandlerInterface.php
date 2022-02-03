<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface HandlerInterface
{
    public function handle(): void;
}