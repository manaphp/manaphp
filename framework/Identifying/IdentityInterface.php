<?php
declare(strict_types=1);

namespace ManaPHP\Identifying;

interface IdentityInterface
{
    public function isGuest(): bool;

    public function getId(?int $default = null): ?int;

    public function getName(?string $default = null): ?string;

    public function getRole(string $default = 'guest'): string;

    public function isRole(string $name): bool;

    public function setRole(string $role): static;

    public function setClaim(string $name, mixed $value): static;

    public function setClaims(array $claims): static;

    public function getClaim(string $name, mixed $default = null): mixed;

    public function getClaims(): array;

    public function hasClaim(string $name): bool;

    public function encode(array $claims): string;
}