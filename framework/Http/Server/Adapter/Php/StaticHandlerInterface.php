<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Php;

interface StaticHandlerInterface
{
    public function isStaticFile(): bool;

    public function getStaticFile(): ?string;

    public function getMimeType(string $file): string;
}