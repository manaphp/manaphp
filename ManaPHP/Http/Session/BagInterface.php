<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session;

interface BagInterface
{
    public function destroy(): void;

    public function set(string $property, mixed $value): static;

    public function get(?string $property = null, mixed $default = null): mixed;

    public function has(string $property): bool;

    public function remove(string $property);
}