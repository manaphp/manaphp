<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Router\MatcherInterface;

interface RouterInterface
{
    public function setPrefix(string $prefix): static;

    public function getPrefix(): string;

    public function add(string $method, string $pattern, string|array $handler): void;

    public function addAny(string $pattern, string|array $handler): void;

    public function addGet(string $pattern, string|array $handler): void;

    public function addPost(string $pattern, string|array $handler): void;

    public function addPut(string $pattern, string|array $handler): void;

    public function addPatch(string $pattern, string|array $handler): void;

    public function addDelete(string $pattern, string|array $handler): void;

    public function addHead(string $pattern, string|array $handler): void;

    public function addOptions(string $pattern, string|array $handler): void;

    public function addRest(string $pattern, string $controller): void;

    public function match(?string $uri = null, ?string $method = null): ?MatcherInterface;

    public function getRewriteUri(): string;

    public function createUrl(string|array $args, bool|string $scheme = false): string;
}
