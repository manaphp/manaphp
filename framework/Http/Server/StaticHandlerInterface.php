<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server;

interface StaticHandlerInterface
{
    public function start(string $doc_root, string $prefix): void;

    public function isFile(string $uri): bool;

    public function getFile(string $uri): ?string;

    public function getMimeType(string $file): string;
}