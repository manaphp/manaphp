<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface AuthorizationInterface
{
    public function getPermissions(string $controller): array;

    public function buildAllowed(string $role, array $granted = []): array;

    public function getAllowed(string $role): string;

    public function isAllowed(string $permission, ?array $roles = null): bool;

    public function authorize(): void;
}