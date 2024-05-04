<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface DispatcherInterface
{
    public function getHandler(): ?string;

    public function getAction(): ?string;

    public function getController(): ?string;

    public function getParams(): array;

    public function dispatch(string $handler, array $params): mixed;

    public function isInvoking(): bool;
}