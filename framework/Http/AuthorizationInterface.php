<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface AuthorizationInterface
{
    public function buildAllowed(string $role, array $explicit_permissions = []): array;

    public function getAllowed(string $role): string;

    public function isAllowed(?string $permission = null, ?string $role = null): bool;

    public function authorize(): void;
}