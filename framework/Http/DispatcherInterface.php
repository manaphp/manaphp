<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface DispatcherInterface
{
    public function getArea(): ?string;

    public function getController(): ?string;

    public function getAction(): ?string;

    public function getParams(): array;

    public function getParam(int|string $name, mixed $default = null): mixed;

    public function hasParam(string $name): bool;

    public function getHandler(): ?string;

    public function getControllerInstance(): ?object;

    public function dispatch(?string $area, string $controller, string $action, array $params): mixed;

    public function isInvoking(): bool;
}