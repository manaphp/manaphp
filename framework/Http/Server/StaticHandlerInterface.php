<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server;

interface StaticHandlerInterface
{
    public function start(string $doc_root, string $prefix): void;

    public function isStaticFile(): bool;

    public function send(): void;
}