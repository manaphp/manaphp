<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Router\RouteInterface;

interface RouterInterface
{
    public function isCaseSensitive(): bool;

    public function setPrefix(string $prefix): static;

    public function getPrefix(): string;

    public function setAreas(?array $areas = null): static;

    public function getAreas(): array;

    public function add(string $method, string $pattern, string|array $handler): RouteInterface;

    public function addGet(string $pattern, string|array $handler): RouteInterface;

    public function addPost(string $pattern, string|array $handler): RouteInterface;

    public function addPut(string $pattern, string|array $handler): RouteInterface;

    public function addPatch(string $pattern, string|array $handler): RouteInterface;

    public function addDelete(string $pattern, string|array $handler): RouteInterface;

    public function addHead(string $pattern, string|array $handler): RouteInterface;

    public function addRest(string $pattern, ?string $controller = null): RouteInterface;

    public function match(?string $uri = null, ?string $method = null): bool;

    public function getRewriteUri(): string;

    public function getArea(): ?string;

    public function setArea(string $area): static;

    public function getController(): ?string;

    public function setController(string $controller): static;

    public function getAction(): ?string;

    public function setAction(string $action): static;

    public function getParams(): array;

    public function setParams(array $params): static;

    public function wasMatched(): bool;

    public function setMatched(bool $matched): static;

    public function createUrl(string|array $args, bool|string $scheme = false): string;
}
