<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface  InputInterface
{
    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name, mixed $default = null): mixed;

    public function type(string $type, string $name, array $constraints = [], mixed $default = null): mixed;

    public function string(string $name, array $constraints = [], ?string $default = null): string;

    public function float(string $name, array $constraints = [], ?float $default = null): float;

    public function int(string $name, array $constraints = [], ?int $default = null): int;

    public function array(string $name, array $constraints = [], ?array $default = null): array;

    public function bool(string $name, array $constraints = [], ?bool $default = null): bool;

    public function bit(string $name, array $constraints = [], ?int $default = null): int;

    public function mixed(string $name, array $constraints = [], mixed $default = null): mixed;
}