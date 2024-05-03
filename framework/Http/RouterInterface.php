<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Router\MatcherInterface;
use ManaPHP\Http\Router\RouteInterface;

interface RouterInterface
{
    public function isCaseSensitive(): bool;

    public function setPrefix(string $prefix): static;

    public function getPrefix(): string;

    public function addWithMethod(string $method, string $pattern, string|array $handler): RouteInterface;

    public function add(string $pattern, string|array $handler): RouteInterface;

    public function addGet(string $pattern, string|array $handler): RouteInterface;

    public function addPost(string $pattern, string|array $handler): RouteInterface;

    public function addPut(string $pattern, string|array $handler): RouteInterface;

    public function addPatch(string $pattern, string|array $handler): RouteInterface;

    public function addDelete(string $pattern, string|array $handler): RouteInterface;

    public function addHead(string $pattern, string|array $handler): RouteInterface;

    public function addRest(string $pattern, string $controller): RouteInterface;

    public function match(?string $uri = null, ?string $method = null): ?MatcherInterface;

    public function getRewriteUri(): string;

    public function createUrl(string|array $args, bool|string $scheme = false): string;
}
