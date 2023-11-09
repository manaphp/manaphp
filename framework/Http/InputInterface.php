<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface  InputInterface
{
    public function has(string $name): bool;

    public function get(string $name, mixed $default = null): mixed;

    public function type(string $type, string $name, array $rules = [], mixed $default = null): mixed;

    public function string(string $name, array $rules = [], ?string $default = null): string;

    public function float(string $name, array $rules = [], ?float $default = null): float;

    public function int(string $name, array $rules = [], ?int $default = null): int;

    public function array(string $name, array $rules = [], ?array $default = null): array;

    public function bool(string $name, array $rules = [], ?bool $default = null): bool;

    public function bit(string $name, array $rules = [], ?int $default = null): int;

    public function mixed(string $name, array $rules = [], mixed $default = null): mixed;
}